<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\WebhookDeliveryStatus;
use App\Events\Payments\PaymentCompleted;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookEnvelopeFactory;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Jobs\NotTenantAware;

final readonly class DispatchPaymentCompletedWebhooksListener implements NotTenantAware, ShouldQueueAfterCommit
{
    public function __construct(
        private WebhookEnvelopeFactory $webhookEnvelopeFactory,
    ) {}

    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $payment = Payment::query()
            ->with('ticketOrder.event')
            ->find($event->payment->payment_id);

        $order = $payment?->ticketOrder;
        $organizerId = $order?->event?->organizer_id;

        if ($payment === null || $order === null || $organizerId === null) {
            return;
        }

        $organizer = Organizer::query()->find($organizerId);

        if ($organizer === null) {
            return;
        }

        Webhook::query()
            ->where('organizer_id', $organizerId)
            ->where('enabled', true)
            ->each(function (Webhook $webhook) use ($organizer, $payment, $organizerId): void {
                $delivery = WebhookDelivery::query()->firstOrCreate(
                    [
                        'webhook_id' => $webhook->webhook_id,
                        'payment_id' => $payment->payment_id,
                    ],
                    [
                        'delivery_id' => Str::uuid()->toString(),
                        'organizer_id' => $organizerId,
                        'event' => 'payment.completed',
                        'version' => 'v1',
                        'envelope' => '{}',
                        'status' => WebhookDeliveryStatus::Pending,
                        'occurred_at' => now(),
                    ],
                );

                if (!$delivery->wasRecentlyCreated) {
                    return;
                }

                $delivery->update([
                    'envelope' => ($this->webhookEnvelopeFactory)->make($delivery),
                ]);

                $organizer->execute(function () use ($organizerId, $delivery): void {
                    dispatch(new DeliverWebhookJob($organizerId, $delivery->webhook_delivery_id))->afterCommit();
                });
            });
    }
}
