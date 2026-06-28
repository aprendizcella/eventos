<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Events;

use App\Enums\EventVisibility;
use Carbon\Carbon;

final readonly class CreateEventDto
{
    public function __construct(
        public int $organizerId,
        public string $title,
        public string $slug,
        public ?string $description = null,
        public ?Carbon $startsAt = null,
        public ?Carbon $endsAt = null,
        public ?int $categoryId = null,
        public ?int $venueId = null,
        public EventVisibility $visibility = EventVisibility::Private,
    ) {}
}
