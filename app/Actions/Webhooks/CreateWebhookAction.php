<?php

declare(strict_types=1);

namespace App\Actions\Webhooks;

use App\DataTransferObjects\Webhooks\CreateWebhookDto;
use App\DataTransferObjects\Webhooks\WebhookSecretDisclosureDto;
use App\Models\Organizer;
use App\Models\Webhook;

final readonly class CreateWebhookAction
{
    public function __invoke(Organizer $organizer, CreateWebhookDto $dto): WebhookSecretDisclosureDto
    {
        $secret = bin2hex(random_bytes(32));
        $webhook = Webhook::query()->create([
            'organizer_id' => $organizer->id,
            'endpoint' => $dto->endpoint,
            'secret' => $secret,
            'enabled' => true,
        ]);

        return new WebhookSecretDisclosureDto($webhook, $secret);
    }
}
