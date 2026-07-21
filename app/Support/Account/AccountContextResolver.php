<?php

declare(strict_types=1);

namespace App\Support\Account;

use App\Models\Organizer;
use App\Models\User;

final class AccountContextResolver
{
    public function resolve(User $user): AccountContext
    {
        return new AccountContext(
            roleLabel: $this->resolveRoleLabel($user),
            organizerLabel: $this->resolveOrganizerLabel($user),
        );
    }

    private function resolveRoleLabel(User $user): string
    {
        $roles = $user->getRoleNames();

        // Fallback: If no roles in current context, check if user has global roles
        if ($roles->isEmpty() && getPermissionsTeamId() !== 0) {
            $currentTeamId = getPermissionsTeamId();
            setPermissionsTeamId(0);
            $user->unsetRelation('roles');
            $roles = $user->getRoleNames();
            setPermissionsTeamId($currentTeamId);
            $user->unsetRelation('roles');
        }

        if ($roles->isEmpty()) {
            return __('No role assigned');
        }

        $priority = ['super_admin', 'platform_admin', 'attendee'];

        foreach ($priority as $roleName) {
            if ($roles->contains($roleName)) {
                return $this->formatRoleLabel($roleName);
            }
        }

        return $this->formatRoleLabel($roles->first());
    }

    private function resolveOrganizerLabel(User $user): string
    {
        $organizer = $user->currentOrganizer();

        if (!$organizer instanceof Organizer) {
            return __('No organizer selected');
        }

        return $organizer->name;
    }

    private function formatRoleLabel(string $roleName): string
    {
        return str_replace('_', ' ', ucwords($roleName, '_'));
    }
}
