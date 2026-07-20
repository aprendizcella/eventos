<?php

declare(strict_types=1);

namespace App\Actions\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class SuspendEventAction
{
    public function __invoke(Event $event, string $reason, User $actor): Event
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to suspend an event.',
            ]);
        }

        $event->previous_status = $event->status->value;
        $event->status = EventStatus::Suspended;
        $event->suspended_at = now();
        $event->save();

        activity()
            ->performedOn($event)
            ->causedBy($actor)
            ->withProperties(['reason' => $reason])
            ->log('suspended');

        return $event;
    }
}
