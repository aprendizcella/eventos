<?php

declare(strict_types=1);

namespace App\Http\Resources\Webhooks;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Webhook */
final class WebhookResource extends JsonResource
{
    /**
     * @return array{id: int, endpoint: string, enabled: bool, created_at: string, updated_at: string}
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->webhook_id,
            'endpoint' => $this->endpoint,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at?->toIso8601String() ?? '',
            'updated_at' => $this->updated_at?->toIso8601String() ?? '',
        ];
    }
}
