<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final class UpdatePasswordAction
{
    public function __invoke(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);
    }
}
