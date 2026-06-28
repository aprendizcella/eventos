<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\DataTransferObjects\Venues\UpdateVenueDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVenueRequest extends FormRequest
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

    public function toDto(): UpdateVenueDto
    {
        $data = $this->validated();

        return new UpdateVenueDto(
            name: (string) $data['name'],
            address: (string) $data['address'],
            city: isset($data['city']) ? (string) $data['city'] : null,
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}
