<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;

class OrganizerPolicy
{
    public function view(User $user, Organizer $organizer): bool
    {
        // Global admins can view any organizer
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $organizer->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'platform_admin']);
    }

    public function update(User $user, Organizer $organizer): bool
    {
        return $this->isGlobalAdmin($user) || $this->isOrganizerAdmin($user, $organizer);
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $organizer->exists && $user->hasRole(['super_admin', 'platform_admin']);
    }

    public function manageTeam(User $user, Organizer $organizer): bool
    {
        return $this->update($user, $organizer);
    }

    public function viewReports(User $user, Organizer $organizer): bool
    {
        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        return $this->isOrganizerAdmin($user, $organizer);
    }

    private function isGlobalAdmin(User $user): bool
    {
        return $user->hasRole(['super_admin', 'platform_admin']);
    }

    private function isOrganizerAdmin(User $user, Organizer $organizer): bool
    {
        $pivot = $organizer->users()
            ->where('users.id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->getAttribute('role') === OrganizerRoles::Admin->value;
    }
}
