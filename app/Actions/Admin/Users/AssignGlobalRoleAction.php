<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Role;

final class AssignGlobalRoleAction
{
    public function __invoke(User $executor, User $target, string $roleName): User
    {
        if (!$executor->hasRole('super_admin')) {
            throw new AuthorizationException('Only super admins can assign global roles.');
        }

        // Ensure the role exists with team_id 0
        Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'organizer_id' => 0]);

        $target->assignRole($roleName);

        return $target;
    }
}
