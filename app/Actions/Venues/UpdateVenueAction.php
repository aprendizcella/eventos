<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\DataTransferObjects\Venues\UpdateVenueDto;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

final readonly class UpdateVenueAction
{
    public function __invoke(Venue $venue, UpdateVenueDto $dto): Venue
    {
        return DB::transaction(function () use ($venue, $dto): Venue {
            $venue->update([
                'name' => $dto->name,
                'address' => $dto->address,
                'city' => $dto->city,
                'capacity' => $dto->capacity,
                'description' => $dto->description,
            ]);

            return $venue->refresh();
        });
    }
}
