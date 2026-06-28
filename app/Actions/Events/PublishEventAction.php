<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PublishEventAction
{
    public function __invoke(Event $event): Event
    {
        $allowedFrom = [EventStatus::Draft, EventStatus::Configured, EventStatus::Paused];

        if (!in_array($event->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot publish an event with status: '.$event->status->value,
            ]);
        }

        $errors = [];

        if (empty($event->title)) {
            $errors['title'] = 'Title is required to publish an event.';
        }

        if ($event->starts_at === null) {
            $errors['starts_at'] = 'Start date is required to publish an event.';
        }

        if (empty($event->description)) {
            $errors['description'] = 'Description is required to publish an event.';
        }

        if ($event->category_id === null) {
            $errors['category_id'] = 'Category is required to publish an event.';
        }

        if ($event->venue_id === null) {
            $errors['venue_id'] = 'Venue is required to publish an event.';
        }

        if ($event->starts_at !== null && $event->ends_at !== null && $event->ends_at->lte($event->starts_at)) {
            $errors['ends_at'] = 'End date must be after start date.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($event): Event {
            $event->update(['status' => EventStatus::Published]);

            activity()
                ->performedOn($event)
                ->withProperties([
                    'attributes' => ['status' => EventStatus::Published->value],
                    'old' => ['status' => $event->getOriginal('status')],
                ])
                ->log('published');

            return $event->refresh();
        });
    }
}
