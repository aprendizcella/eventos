<?php

declare(strict_types=1);

namespace App\Http\Requests\Organizers;

use App\DataTransferObjects\Organizers\CreateOrganizerDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateOrganizerRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizers,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:organizers,domain'],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function toDto(): CreateOrganizerDto
    {
        $data = $this->validated();

        return new CreateOrganizerDto(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            domain: isset($data['domain']) ? (string) $data['domain'] : null,
            settings: isset($data['settings']) ? (array) $data['settings'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'active',
        );
    }
}
