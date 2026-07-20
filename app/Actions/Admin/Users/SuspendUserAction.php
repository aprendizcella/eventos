<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SuspendUserAction
{
    public function __invoke(User $user): User
    {
        return DB::transaction(function () use ($user) {
            if ($user->hasRole('super_admin')) {
                $activeSuperAdmins = User::query()
                    ->role('super_admin')
                    ->whereNull('suspended_at')
                    ->lockForUpdate()
                    ->count();

                if ($activeSuperAdmins <= 1 && !$user->isSuspended()) {
                    throw ValidationException::withMessages([
                        'user' => 'Cannot suspend the last active super admin.',
                    ]);
                }
            }

            $user->suspended_at = now();
            $user->save();
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            return $user;
        });
    }
}
