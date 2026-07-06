<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read Attendee $resource
 */
final class AttendeeApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'attendee_id' => $this->resource->attendee_id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'status' => $this->resource->status->value,
            'unique_code' => $this->resource->unique_code,
            'sequence' => $this->resource->sequence,
            'checked_in' => (bool) $this->resource->getAttribute('is_checked_in'),
            'custom_answers' => $this->resource->custom_answers ?? [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
