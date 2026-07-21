<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any activity logs globally.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRole('super_admin');
    }
}
