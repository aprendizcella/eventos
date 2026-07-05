<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;

class EventPolicy
{
    public function viewAny(User $user, Organizer $organizer): bool
    {
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $organizer->users()->where('users.id', $user->id)->exists();
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->belongsToOrganizer($user, $event->organizer_id);
    }

    public function create(User $user, Organizer $organizer): bool
    {
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->hasRoleInOrganizer($user, $organizer, [
            OrganizerRoles::Admin,
            OrganizerRoles::Editor,
        ]);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function publish(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event)
            && in_array($event->status, [EventStatus::Draft, EventStatus::Configured, EventStatus::Paused], true);
    }

    public function pause(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event)
            && $event->status === EventStatus::Published;
    }

    public function cancel(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event)
            && in_array($event->status, [EventStatus::Draft, EventStatus::Configured, EventStatus::Published, EventStatus::Paused], true);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function viewCheckIn(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    public function checkIn(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function undoCheckIn(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function manageWaitlist(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function manageCustomQuestions(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function sendMessages(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    public function exportAttendees(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    private function canManageEvent(User $user, Event $event): bool
    {
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->hasRoleForEvent($user, $event, [
            OrganizerRoles::Admin,
            OrganizerRoles::Editor,
        ]);
    }

    private function belongsToOrganizer(User $user, int $organizerId): bool
    {
        return $user->organizers()
            ->where('organizers.id', $organizerId)
            ->exists();
    }

    /**
     * @param  list<OrganizerRoles>  $allowedRoles
     */
    private function hasRoleInOrganizer(User $user, Organizer $organizer, array $allowedRoles): bool
    {
        $pivot = $organizer->users()
            ->where('users.id', $user->id)
            ->first();

        if (!$pivot) {
            return false;
        }

        $role = $pivot->pivot->getAttribute('role');

        return array_any($allowedRoles, fn ($allowed) => $role === $allowed->value);
    }

    /**
     * @param  list<OrganizerRoles>  $allowedRoles
     */
    private function hasRoleForEvent(User $user, Event $event, array $allowedRoles): bool
    {
        $pivot = $user->organizers()
            ->where('organizers.id', $event->organizer_id)
            ->first();

        if (!$pivot) {
            return false;
        }

        $role = $pivot->pivot->getAttribute('role');

        return array_any($allowedRoles, fn ($allowed) => $role === $allowed->value);
    }
}
