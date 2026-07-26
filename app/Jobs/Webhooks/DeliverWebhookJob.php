<?php

declare(strict_types=1);

namespace App\Jobs\Webhooks;

use App\Enums\WebhookDeliveryStatus;
use App\Models\Organizer;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\RetryableWebhookDeliveryException;
use App\Services\Webhooks\UnsafeWebhookDestinationException;
use App\Services\Webhooks\WebhookDestinationPolicy;
use App\Services\Webhooks\WebhookDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Throwable;

final class DeliverWebhookJob implements NotTenantAware, ShouldQueue
{
    use \Illuminate\Foundation\Queue\Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 1800, 7200];

    public int $timeout = 30;

    public function __construct(
        public int $organizerId,
        public int $webhookDeliveryId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(WebhookDestinationPolicy $destinationPolicy, WebhookDispatcher $dispatcher): void
    {
        $organizer = Organizer::query()->find($this->organizerId);

        if ($organizer === null) {
            return;
        }

        $organizer->execute(function () use ($destinationPolicy, $dispatcher): void {
            $delivery = WebhookDelivery::query()
                ->where('organizer_id', $this->organizerId)
                ->with('webhook')
                ->find($this->webhookDeliveryId);

            if ($delivery === null || $delivery->webhook === null || !$delivery->webhook->enabled) {
                $this->markFailed($delivery, 'missing_or_disabled');

                return;
            }

            $delivery->update([
                'status' => WebhookDeliveryStatus::Delivering,
                'attempts' => $delivery->attempts + 1,
            ]);

            try {
                $destinationPolicy->assertSafe($delivery->webhook->endpoint);
                $response = $dispatcher->send($delivery, $delivery->webhook);
            } catch (UnsafeWebhookDestinationException) {
                $this->markFailed($delivery, 'unsafe_destination');

                return;
            }

            $status = $response->status();

            if ($response->successful()) {
                $delivery->update([
                    'status' => WebhookDeliveryStatus::Succeeded,
                    'response_status' => $status,
                    'delivered_at' => now(),
                    'expires_at' => now()->addDays(30),
                ]);

                return;
            }

            if ($status === 429 || $status >= 500) {
                throw RetryableWebhookDeliveryException::forStatus($status);
            }

            $this->markFailed($delivery, 'http_'.$status, $status);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $organizer = Organizer::query()->find($this->organizerId);

        if ($organizer === null) {
            return;
        }

        $organizer->execute(function () use ($exception): void {
            $delivery = WebhookDelivery::query()
                ->where('organizer_id', $this->organizerId)
                ->find($this->webhookDeliveryId);

            $this->markFailed($delivery, $exception instanceof RetryableWebhookDeliveryException ? 'retries_exhausted' : 'delivery_failed');
        });
    }

    private function markFailed(?WebhookDelivery $delivery, string $reason, ?int $status = null): void
    {
        $delivery?->update([
            'status' => WebhookDeliveryStatus::Failed,
            'response_status' => $status,
            'failure_reason' => $reason,
            'failed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
