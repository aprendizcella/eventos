<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\DataTransferObjects\Events\UpdateEventDto;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;

final readonly class UpdateEventAction
{
    public function __invoke(Event $event, UpdateEventDto $dto): Event
    {
        return DB::transaction(function () use ($event, $dto): Event {
            $data = [
                'title' => $dto->title,
                'slug' => $dto->slug,
            ];

            if ($dto->description !== null) {
                $data['description'] = Purifier::clean($dto->description);
            }

            if ($dto->startsAt instanceof \Carbon\Carbon) {
                $data['starts_at'] = $dto->startsAt;
            }

            if ($dto->endsAt instanceof \Carbon\Carbon) {
                $data['ends_at'] = $dto->endsAt;
            }

            if ($dto->categoryId !== null) {
                $data['category_id'] = $dto->categoryId;
            }

            if ($dto->venueId !== null) {
                $data['venue_id'] = $dto->venueId;
            }

            if ($dto->visibility instanceof \App\Enums\EventVisibility) {
                $data['visibility'] = $dto->visibility;
            }

            $event->update($data);

            return $event->refresh();
        });
    }
}
