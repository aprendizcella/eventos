<?php

declare(strict_types=1);

namespace App\Actions\Webhooks;

use App\Models\Organizer;
use App\Models\Webhook;

final readonly class DisableWebhookAction
{
    public function __invoke(Organizer $organizer, int $webhookId): void
    {
        $webhook = Webhook::query()
            ->where('organizer_id', $organizer->id)
            ->findOrFail($webhookId);

        if (!$webhook->enabled) {
            return;
        }

        $webhook->update(['enabled' => false]);
    }
}
