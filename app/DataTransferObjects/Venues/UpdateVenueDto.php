<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Venues;

final readonly class UpdateVenueDto
{
    public function __construct(
        public string $name,
        public string $address,
        public ?string $city = null,
        public ?int $capacity = null,
        public ?string $description = null,
    ) {}
}
