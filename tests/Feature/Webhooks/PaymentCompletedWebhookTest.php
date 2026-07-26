<?php

declare(strict_types=1);

use App\Enums\WebhookDeliveryStatus;
use App\Events\Payments\PaymentCompleted;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\TicketOrder;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

function paymentForWebhook(Organizer $organizer): Payment
{
    $event = Event::factory()->for($organizer)->create();
    $order = TicketOrder::factory()->for($event)->create();

    return Payment::factory()->for($order, 'ticketOrder')->create();
}

it('creates and queues one canonical delivery per active subscription after payment completion commits', function (): void {
    Queue::fake([DeliverWebhookJob::class]);

    $organizer = Organizer::factory()->create();
    $activeWebhook = Webhook::factory()->for($organizer)->create();
    Webhook::factory()->for($organizer)->create(['enabled' => false]);
    $payment = paymentForWebhook($organizer);

    DB::transaction(function () use ($payment): void {
        event(new PaymentCompleted($payment));

        expect(WebhookDelivery::query()->count())->toBe(0);
    });

    $delivery = WebhookDelivery::query()->sole();
    $envelope = $delivery->envelope;

    expect($delivery->organizer_id)->toBe($organizer->getKey())
        ->and($delivery->webhook_id)->toBe($activeWebhook->webhook_id)
        ->and($delivery->payment_id)->toBe($payment->payment_id)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->delivery_id)->toBeString()
        ->and(Str::isUuid($delivery->delivery_id))->toBeTrue()
        ->and(DB::table('webhook_delivery')->value('envelope'))->not->toBe($envelope)
        ->and($envelope)->toBe(json_encode([
            'version' => 'v1',
            'event' => 'payment.completed',
            'delivery_id' => $delivery->delivery_id,
            'occurred_at' => $delivery->occurred_at->utc()->toISOString(),
            'organizer_id' => $organizer->getKey(),
            'data' => [
                'payment_id' => $payment->payment_id,
                'order_id' => $payment->ticket_order_id,
            ],
        ], JSON_THROW_ON_ERROR));

    Queue::assertPushed(DeliverWebhookJob::class, fn (DeliverWebhookJob $job): bool => $job->organizerId === $organizer->getKey()
        && $job->webhookDeliveryId === $delivery->webhook_delivery_id);
});

it('creates no delivery or queued attempt when payment completion rolls back', function (): void {
    Queue::fake([DeliverWebhookJob::class]);

    $organizer = Organizer::factory()->create();
    Webhook::factory()->for($organizer)->create();
    $payment = paymentForWebhook($organizer);

    try {
        DB::transaction(function () use ($payment): void {
            event(new PaymentCompleted($payment));

            throw new RuntimeException('Force transaction rollback.');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Force transaction rollback.');
    }

    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not fan out to disabled subscriptions', function (): void {
    Queue::fake([DeliverWebhookJob::class]);

    $organizer = Organizer::factory()->create();
    Webhook::factory()->for($organizer)->create(['enabled' => false]);
    $payment = paymentForWebhook($organizer);

    DB::transaction(fn (): mixed => event(new PaymentCompleted($payment)));

    expect(WebhookDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('keeps the v1 envelope allowlisted to payment and order identifiers', function (): void {
    Queue::fake([DeliverWebhookJob::class]);

    $organizer = Organizer::factory()->create();
    Webhook::factory()->for($organizer)->create();
    $payment = paymentForWebhook($organizer);

    DB::transaction(fn (): mixed => event(new PaymentCompleted($payment)));

    /** @var array<string, mixed> $envelope */
    $envelope = json_decode(WebhookDelivery::query()->sole()->envelope, true, 512, JSON_THROW_ON_ERROR);

    expect(array_keys($envelope))->toBe(['version', 'event', 'delivery_id', 'occurred_at', 'organizer_id', 'data'])
        ->and($envelope['data'])->toBe([
            'payment_id' => $payment->payment_id,
            'order_id' => $payment->ticket_order_id,
        ])
        ->and(json_encode($envelope, JSON_THROW_ON_ERROR))->not->toContain('provider_id')
        ->not->toContain('customer')
        ->not->toContain('secret')
        ->not->toContain('amount');
});
