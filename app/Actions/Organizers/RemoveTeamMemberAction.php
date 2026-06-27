<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Support\Facades\DB;

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

            $role = $pivot->pivot->getAttribute('role');

            if ($role === OrganizerRoles::Admin->value) {
                $adminCount = $organizer->users()
                    ->where('organizer_user.role', OrganizerRoles::Admin->value)
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
