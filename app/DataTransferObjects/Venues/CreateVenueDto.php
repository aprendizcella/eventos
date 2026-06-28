<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Venues;

final readonly class CreateVenueDto
{
    public function __construct(
        public int $organizerId,
        public string $name,
        public string $address,
        public ?string $city = null,
        public ?int $capacity = null,
        public ?string $description = null,
    ) {}
}
