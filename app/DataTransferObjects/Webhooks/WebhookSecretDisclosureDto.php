<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Webhooks;

use App\Models\Webhook;

final readonly class WebhookSecretDisclosureDto
{
    public function __construct(
        public Webhook $webhook,
        public string $secret,
    ) {}
}
