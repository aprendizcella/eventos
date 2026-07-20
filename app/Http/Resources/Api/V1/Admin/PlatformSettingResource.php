<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class PlatformSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var \App\Models\PlatformSetting $setting */
        $setting = $this->resource;

        return [
            'settings' => $setting->settings,
            'lock_version' => $setting->lock_version,
            'updated_at' => $setting->updated_at?->toIso8601String(),
        ];
    }
}
