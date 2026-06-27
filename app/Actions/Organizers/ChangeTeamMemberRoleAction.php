<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Support\Facades\DB;

final readonly class ChangeTeamMemberRoleAction
{
    public function __invoke(Organizer $organizer, ChangeTeamMemberRoleDto $dto, User $changedBy): void
    {
        DB::transaction(function () use ($organizer, $dto, $changedBy): void {
            $pivot = $organizer->users()
                ->where('users.id', $dto->userId)
                ->first();

            if (!$pivot) {
                return;
            }

            $currentRole = $pivot->pivot->getAttribute('role');

            if ($currentRole === OrganizerRoles::Admin->value && $dto->role !== OrganizerRoles::Admin->value) {
                $adminCount = $organizer->users()
                    ->where('organizer_user.role', OrganizerRoles::Admin->value)
                    ->count();

                if ($adminCount <= 1) {
                    throw new LastAdminCannotBeRemovedException;
                }
            }

            $organizer->users()->updateExistingPivot($dto->userId, [
                'role' => $dto->role,
            ]);

            activity()
                ->performedOn($organizer)
                ->causedBy($changedBy)
                ->withProperties([
                    'user_id' => $dto->userId,
                    'new_role' => $dto->role,
                ])
                ->log('team_member_role_changed');
        });
    }
}
