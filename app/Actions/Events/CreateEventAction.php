<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\DataTransferObjects\Events\CreateEventDto;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

final readonly class CreateEventAction
{
    public function __invoke(CreateEventDto $dto): Event
    {
        return DB::transaction(function () use ($dto): Event {
            $description = $dto->description !== null
                ? Purifier::clean($dto->description)
                : null;

            return Event::query()->create([
                'organizer_id' => $dto->organizerId,
                'category_id' => $dto->categoryId,
                'venue_id' => $dto->venueId,
                'title' => $dto->title,
                'slug' => $dto->slug,
                'description' => $description,
                'starts_at' => $dto->startsAt,
                'ends_at' => $dto->endsAt,
                'status' => EventStatus::Draft,
                'visibility' => $dto->visibility,
            ]);
        });
    }
}
