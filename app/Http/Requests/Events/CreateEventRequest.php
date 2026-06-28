<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\DataTransferObjects\Events\CreateEventDto;
use App\Enums\EventVisibility;
use Illuminate\Foundation\Http\FormRequest;

final class CreateEventRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:event,slug'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'venue_id' => ['nullable', 'integer', 'exists:venue,venue_id'],
            'visibility' => ['nullable', 'string', 'in:private,public,password_protected'],
        ];
    }

    public function toDto(int $organizerId): CreateEventDto
    {
        $data = $this->validated();

        return new CreateEventDto(
            organizerId: $organizerId,
            title: (string) $data['title'],
            slug: (string) $data['slug'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            startsAt: isset($data['starts_at']) ? \Illuminate\Support\Facades\Date::parse($data['starts_at']) : null,
            endsAt: isset($data['ends_at']) ? \Illuminate\Support\Facades\Date::parse($data['ends_at']) : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            venueId: isset($data['venue_id']) ? (int) $data['venue_id'] : null,
            visibility: isset($data['visibility']) ? EventVisibility::from((string) $data['visibility']) : EventVisibility::Private,
        );
    }
}
