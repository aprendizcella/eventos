<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Organizer
 */
final class OrganizerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     domain: string|null,
     *     settings: array{
     *         social: array{
     *             facebook: string|null,
     *             twitter: string|null,
     *             instagram: string|null,
     *             linkedin: string|null
     *         },
     *         defaults: array{
     *             currency: string,
     *             timezone: string
     *         }
     *     }|null,
     *     status: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'settings' => $this->settings ? [
                'social' => [
                    'facebook' => $this->settings['social']['facebook'] ?? null,
                    'twitter' => $this->settings['social']['twitter'] ?? null,
                    'instagram' => $this->settings['social']['instagram'] ?? null,
                    'linkedin' => $this->settings['social']['linkedin'] ?? null,
                ],
                'defaults' => [
                    'currency' => $this->settings['defaults']['currency'] ?? 'USD',
                    'timezone' => $this->settings['defaults']['timezone'] ?? 'UTC',
                ],
            ] : null,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String() ?? '',
            'updated_at' => $this->updated_at?->toIso8601String() ?? '',
        ];
    }
}
