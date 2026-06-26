<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

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

            $currentRoleId = $pivot->pivot->role_id;
            $adminRole = Role::query()->where('name', 'admin')->first();

            if ($adminRole && $currentRoleId === $adminRole->id && $dto->roleId !== $adminRole->id) {
                $adminCount = $organizer->users()
                    ->where('organizer_user.role_id', $adminRole->id)
                    ->count();

                if ($adminCount <= 1) {
                    throw new LastAdminCannotBeRemovedException;
                }
            }

            $organizer->users()->updateExistingPivot($dto->userId, [
                'role_id' => $dto->roleId,
            ]);

            activity()
                ->performedOn($organizer)
                ->causedBy($changedBy)
                ->withProperties([
                    'user_id' => $dto->userId,
                    'new_role_id' => $dto->roleId,
                ])
                ->log('team_member_role_changed');
        });
    }
}
