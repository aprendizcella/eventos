<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class RemoveTeamMemberAction
{
    public function __invoke(Organizer $organizer, User $userToRemove, User $removedBy): void
    {
        DB::transaction(function () use ($organizer, $userToRemove, $removedBy): void {
            $pivot = $organizer->users()
                ->where('users.id', $userToRemove->id)
                ->first();

            if (!$pivot) {
                return;
            }

            $roleId = $pivot->pivot->role_id;
            $adminRole = Role::query()->where('name', 'admin')->first();

            if ($adminRole && $roleId === $adminRole->id) {
                $adminCount = $organizer->users()
                    ->where('organizer_user.role_id', $adminRole->id)
                    ->count();

                if ($adminCount <= 1) {
                    throw new LastAdminCannotBeRemovedException;
                }
            }

            $organizer->users()->detach($userToRemove->id);

            activity()
                ->performedOn($organizer)
                ->causedBy($removedBy)
                ->withProperties([
                    'user_id' => $userToRemove->id,
                ])
                ->log('team_member_removed');
        });
    }
}
