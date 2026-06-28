<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\DataTransferObjects\Venues\CreateVenueDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateVenueRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDto(int $organizerId): CreateVenueDto
    {
        $data = $this->validated();

        return new CreateVenueDto(
            organizerId: $organizerId,
            name: (string) $data['name'],
            address: (string) $data['address'],
            city: isset($data['city']) ? (string) $data['city'] : null,
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}
