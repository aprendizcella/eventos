<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class RevokeGlobalRoleAction
{
    public function __invoke(User $executor, User $target, string $roleName): User
    {
        if (!$executor->hasRole('super_admin')) {
            throw new AuthorizationException('Only super admins can revoke global roles.');
        }

        $target->removeRole($roleName);

        return $target;
    }
}
