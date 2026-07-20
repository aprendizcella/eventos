<?php

declare(strict_types=1);

namespace App\Actions\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use LogicException;

final class RestoreEventAction
{
    public function __invoke(Event $event, User $actor): Event
    {
        if ($event->previous_status === null) {
            throw new LogicException('Cannot restore an event without a previous status.');
        }

        $event->status = EventStatus::from($event->previous_status);
        $event->previous_status = null;
        $event->suspended_at = null;
        $event->save();

        activity()
            ->performedOn($event)
            ->causedBy($actor)
            ->log('restored');

        return $event;
    }
}
