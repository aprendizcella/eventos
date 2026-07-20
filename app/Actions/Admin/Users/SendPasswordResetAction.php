<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetAction
{
    public function __invoke(User $user): void
    {
        Password::broker()->sendResetLink(['email' => $user->email]);
    }
}
