<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use RuntimeException;

final class UnsafeWebhookDestinationException extends RuntimeException
{
    public static function forEndpoint(): self
    {
        return new self('Webhook destination did not pass the public HTTPS destination policy.');
    }
}
