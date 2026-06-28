<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\DataTransferObjects\Venues\CreateVenueDto;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

final readonly class CreateVenueAction
{
    public function __invoke(CreateVenueDto $dto): Venue
    {
        return DB::transaction(fn (): Venue => Venue::query()->create([
            'organizer_id' => $dto->organizerId,
            'name' => $dto->name,
            'address' => $dto->address,
            'city' => $dto->city,
            'capacity' => $dto->capacity,
            'description' => $dto->description,
        ]));
    }
}
