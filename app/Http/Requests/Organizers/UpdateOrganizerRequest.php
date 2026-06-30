<?php

declare(strict_types=1);

namespace App\Http\Requests\Organizers;

use App\DataTransferObjects\Organizers\UpdateOrganizerDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrganizerRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $routeParam = $this->route('organizer');
        $organizerId = $routeParam instanceof \App\Models\Organizer ? $routeParam->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizers,slug,'.$organizerId],
            'domain' => ['nullable', 'string', 'max:255', 'unique:organizers,domain,'.$organizerId],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function toDto(): UpdateOrganizerDto
    {
        $data = $this->validated();

        return new UpdateOrganizerDto(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            domain: array_key_exists('domain', $data) ? ($data['domain'] !== null ? (string) $data['domain'] : '') : null,
            settings: isset($data['settings']) ? (array) $data['settings'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
        );
    }
}
