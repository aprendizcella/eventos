<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Webhooks;

final readonly class CreateWebhookDto
{
    public function __construct(public string $endpoint) {}
}
