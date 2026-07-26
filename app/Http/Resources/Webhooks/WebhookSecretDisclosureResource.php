<?php

declare(strict_types=1);

namespace App\Http\Resources\Webhooks;

use App\DataTransferObjects\Webhooks\WebhookSecretDisclosureDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin WebhookSecretDisclosureDto */
final class WebhookSecretDisclosureResource extends JsonResource
{
    /**
     * @return array{id: int, endpoint: string, enabled: bool, created_at: string, updated_at: string, secret: string}
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            ...new WebhookResource($this->webhook)->toArray($request),
            'secret' => $this->secret,
        ];
    }
}
