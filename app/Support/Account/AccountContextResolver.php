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
