<?php

declare(strict_types=1);

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\RetryableWebhookDeliveryException;
use App\Services\Webhooks\UnsafeWebhookDestinationException;
use App\Services\Webhooks\WebhookDestinationPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('delivers the encrypted canonical body with an HMAC signature', function (): void {
    Http::fake(['https://hooks.example.com/*' => Http::response([], 204)]);
    app()->instance(WebhookDestinationPolicy::class, Mockery::mock(WebhookDestinationPolicy::class, function (Mockery\MockInterface $mock): void {
        $mock->shouldReceive('assertSafe')->once();
    }));

    $delivery = WebhookDelivery::factory()->create([
        'envelope' => '{"event":"payment.completed"}',
    ]);
    $delivery->webhook->update(['endpoint' => 'https://hooks.example.com/events']);

    new DeliverWebhookJob($delivery->organizer_id, $delivery->webhook_delivery_id)->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    );

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Succeeded)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->response_status)->toBe(204)
        ->and($delivery->failure_reason)->toBeNull();

    Http::assertSent(fn (Illuminate\Http\Client\Request $request): bool => $request->body() === '{"event":"payment.completed"}'
        && $request->header('X-HiEvents-Event')[0] === $delivery->event
        && $request->header('X-HiEvents-Delivery-Id')[0] === $delivery->delivery_id
        && str_starts_with((string) $request->header('X-HiEvents-Signature')[0], 'v1='));
});

it('fails terminally for a non-retryable HTTP response without retaining the body', function (): void {
    Http::fake(['https://hooks.example.com/*' => Http::response('private response body', 400)]);
    app()->instance(WebhookDestinationPolicy::class, Mockery::mock(WebhookDestinationPolicy::class, function (Mockery\MockInterface $mock): void {
        $mock->shouldReceive('assertSafe')->once();
    }));

    $delivery = WebhookDelivery::factory()->create();
    $delivery->webhook->update(['endpoint' => 'https://hooks.example.com/events']);

    new DeliverWebhookJob($delivery->organizer_id, $delivery->webhook_delivery_id)->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    );

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_status)->toBe(400)
        ->and($delivery->failure_reason)->toBe('http_400')
        ->and($delivery->failure_reason)->not->toContain('private response body');
});

it('releases retryable responses and marks the delivery terminal after exhaustion', function (): void {
    Http::fake(['https://hooks.example.com/*' => Http::response([], 503)]);
    app()->instance(WebhookDestinationPolicy::class, Mockery::mock(WebhookDestinationPolicy::class, function (Mockery\MockInterface $mock): void {
        $mock->shouldReceive('assertSafe')->once();
    }));

    $delivery = WebhookDelivery::factory()->create();
    $delivery->webhook->update(['endpoint' => 'https://hooks.example.com/events']);
    $job = new DeliverWebhookJob($delivery->organizer_id, $delivery->webhook_delivery_id);

    expect(fn (): mixed => $job->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    ))->toThrow(RetryableWebhookDeliveryException::class);

    $job->failed(RetryableWebhookDeliveryException::forStatus(503));
    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->fresh()->failure_reason)->toBe('retries_exhausted');
});

it('fails without making a request when DNS policy rejects the destination', function (): void {
    Http::fake();
    app()->instance(WebhookDestinationPolicy::class, Mockery::mock(WebhookDestinationPolicy::class, function (Mockery\MockInterface $mock): void {
        $mock->shouldReceive('assertSafe')->once()->andThrow(UnsafeWebhookDestinationException::forEndpoint());
    }));

    $delivery = WebhookDelivery::factory()->create();
    $delivery->webhook->update(['endpoint' => 'https://hooks.example.com/events']);

    new DeliverWebhookJob($delivery->organizer_id, $delivery->webhook_delivery_id)->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    );

    expect($delivery->fresh()->failure_reason)->toBe('unsafe_destination');
    Http::assertNothingSent();
});

it('fails terminally when the subscription is disabled before delivery', function (): void {
    $delivery = WebhookDelivery::factory()->create();
    $delivery->webhook->update(['enabled' => false]);

    new DeliverWebhookJob($delivery->organizer_id, $delivery->webhook_delivery_id)->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    );

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->fresh()->failure_reason)->toBe('missing_or_disabled');
});

it('does nothing when the organizer context no longer exists', function (): void {
    $job = new DeliverWebhookJob(999999, 999999);

    $job->handle(
        resolve(WebhookDestinationPolicy::class),
        resolve(App\Services\Webhooks\WebhookDispatcher::class),
    );

    expect(true)->toBeTrue();
});

it('purges deliveries after the thirty day retention period', function (): void {
    $expired = WebhookDelivery::factory()->create([
        'status' => WebhookDeliveryStatus::Succeeded,
        'expires_at' => now()->subSecond(),
    ]);
    $active = WebhookDelivery::factory()->create([
        'status' => WebhookDeliveryStatus::Failed,
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('webhooks:purge-expired-deliveries')->assertSuccessful();

    expect(WebhookDelivery::withTrashed()->find($expired->webhook_delivery_id))->toBeNull()
        ->and(WebhookDelivery::query()->find($active->webhook_delivery_id))->not->toBeNull();
});
