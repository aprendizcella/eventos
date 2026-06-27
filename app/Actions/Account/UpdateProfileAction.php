<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

final class UpdateProfileAction
{
    public function __invoke(User $user, string $name): void
    {
        $user->update(['name' => $name]);
    }
}
