<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\User;

final class RestoreUserAction
{
    public function __invoke(User $user): User
    {
        $user->suspended_at = null;
        $user->save();

        return $user;
    }
}
