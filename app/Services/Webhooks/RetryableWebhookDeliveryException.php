<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use RuntimeException;

final class RetryableWebhookDeliveryException extends RuntimeException
{
    public static function forStatus(int $status): self
    {
        return new self("Webhook endpoint returned retryable status {$status}.");
    }
}
