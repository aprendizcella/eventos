<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\DataTransferObjects\Events\UpdateEventDto;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEventRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $routeParam = $this->route('event');
        $eventId = $routeParam instanceof Event ? $routeParam->event_id : null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:event,slug,'.$eventId.',event_id'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'venue_id' => ['nullable', 'integer', 'exists:venue,venue_id'],
            'visibility' => ['nullable', 'string', 'in:private,public,password_protected'],
        ];
    }

    public function toDto(): UpdateEventDto
    {
        $data = $this->validated();

        return new UpdateEventDto(
            title: (string) $data['title'],
            slug: (string) $data['slug'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            startsAt: isset($data['starts_at']) ? \Illuminate\Support\Facades\Date::parse($data['starts_at']) : null,
            endsAt: isset($data['ends_at']) ? \Illuminate\Support\Facades\Date::parse($data['ends_at']) : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            venueId: isset($data['venue_id']) ? (int) $data['venue_id'] : null,
            visibility: isset($data['visibility']) ? EventVisibility::from((string) $data['visibility']) : null,
        );
    }
}
