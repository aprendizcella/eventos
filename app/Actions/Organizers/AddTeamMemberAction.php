<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\DataTransferObjects\Organizers\AddTeamMemberDto;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AddTeamMemberAction
{
    public function __invoke(Organizer $organizer, AddTeamMemberDto $dto, User $addedBy): void
    {
        DB::transaction(function () use ($organizer, $dto, $addedBy): void {
            $organizer->users()->attach($dto->userId, [
                'role_id' => $dto->roleId,
            ]);

            activity()
                ->performedOn($organizer)
                ->causedBy($addedBy)
                ->withProperties([
                    'user_id' => $dto->userId,
                    'role_id' => $dto->roleId,
                ])
                ->log('team_member_added');
        });
    }
}
