<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Event
 */
final class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     event_id: int,
     *     organizer_id: int,
     *     category_id: int|null,
     *     venue_id: int|null,
     *     title: string,
     *     slug: string,
     *     description: string|null,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     status: string,
     *     visibility: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->event_id,
            'organizer_id' => $this->organizer_id,
            'category_id' => $this->category_id,
            'venue_id' => $this->venue_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status->value,
            'visibility' => $this->visibility->value,
            'created_at' => $this->created_at?->toIso8601String() ?? '',
            'updated_at' => $this->updated_at?->toIso8601String() ?? '',
        ];
    }
}
