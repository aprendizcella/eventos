<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Support\Organizers\OrganizerRoles;

class VenuePolicy
{
    public function viewAny(User $user, Organizer $organizer): bool
    {
        if ($user->hasGlobalRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $organizer->users()->where('users.id', $user->id)->exists();
    }

    public function view(User $user, Venue $venue): bool
    {
        if ($user->hasGlobalRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->belongsToOrganizer($user, $venue->organizer_id);
    }

    public function create(User $user, Organizer $organizer): bool
    {
        if ($user->hasGlobalRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->hasRoleInOrganizer($user, $organizer, [
            OrganizerRoles::Admin,
            OrganizerRoles::Editor,
        ]);
    }

    public function update(User $user, Venue $venue): bool
    {
        if ($user->hasGlobalRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->hasRoleForVenue($user, $venue, [
            OrganizerRoles::Admin,
            OrganizerRoles::Editor,
        ]);
    }

    public function delete(User $user, Venue $venue): bool
    {
        if ($user->hasGlobalRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->hasRoleForVenue($user, $venue, [
            OrganizerRoles::Admin,
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
    private function hasRoleForVenue(User $user, Venue $venue, array $allowedRoles): bool
    {
        $pivot = $user->organizers()
            ->where('organizers.id', $venue->organizer_id)
            ->first();

        if (!$pivot) {
            return false;
        }

        $role = $pivot->pivot->getAttribute('role');

        return array_any($allowedRoles, fn ($allowed) => $role === $allowed->value);
    }
}
