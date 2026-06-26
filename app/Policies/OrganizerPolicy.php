<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;
use Spatie\Permission\Models\Role;

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
        // Global admins can update any organizer
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return $this->isOrganizerAdmin($user, $organizer);
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $user->hasRole(['super_admin', 'platform_admin']);
    }

    public function manageTeam(User $user, Organizer $organizer): bool
    {
        return $this->isOrganizerAdmin($user, $organizer);
    }

    private function isOrganizerAdmin(User $user, Organizer $organizer): bool
    {
        $adminRole = Role::query()->where('name', 'admin')->first();

        if (!$adminRole) {
            return false;
        }

        $pivot = $organizer->users()
            ->where('users.id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->role_id === $adminRole->id;
    }
}
