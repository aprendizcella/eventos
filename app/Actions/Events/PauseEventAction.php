<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PauseEventAction
{
    public function __invoke(Event $event): Event
    {
        $allowedFrom = [EventStatus::Published];

        if (!in_array($event->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot pause an event with status: '.$event->status->value,
            ]);
        }

        return DB::transaction(function () use ($event): Event {
            $event->update(['status' => EventStatus::Paused]);

            activity()
                ->performedOn($event)
                ->withProperties([
                    'attributes' => ['status' => EventStatus::Paused->value],
                    'old' => ['status' => $event->getOriginal('status')],
                ])
                ->log('paused');

            return $event->refresh();
        });
    }
}
