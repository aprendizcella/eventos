<?php

declare(strict_types=1);

namespace App\Actions\Webhooks;

use App\DataTransferObjects\Webhooks\WebhookSecretDisclosureDto;
use App\Models\Organizer;
use App\Models\Webhook;

final readonly class RotateWebhookSecretAction
{
    public function __invoke(Organizer $organizer, int $webhookId): WebhookSecretDisclosureDto
    {
        $webhook = Webhook::query()
            ->where('organizer_id', $organizer->id)
            ->findOrFail($webhookId);
        $secret = bin2hex(random_bytes(32));

        $webhook->update(['secret' => $secret]);

        return new WebhookSecretDisclosureDto($webhook, $secret);
    }
}
