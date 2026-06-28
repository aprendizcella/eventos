<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelEventAction
{
    public function __invoke(Event $event): Event
    {
        $allowedFrom = [
            EventStatus::Draft,
            EventStatus::Configured,
            EventStatus::Published,
            EventStatus::Paused,
        ];

        if (!in_array($event->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot cancel an event with status: '.$event->status->value,
            ]);
        }

        return DB::transaction(function () use ($event): Event {
            $event->update(['status' => EventStatus::Cancelled]);

            activity()
                ->performedOn($event)
                ->withProperties([
                    'attributes' => ['status' => EventStatus::Cancelled->value],
                    'old' => ['status' => $event->getOriginal('status')],
                ])
                ->log('cancelled');

            return $event->refresh();
        });
    }
}
