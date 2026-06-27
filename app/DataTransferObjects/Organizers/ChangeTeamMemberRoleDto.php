<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Organizers;

final readonly class ChangeTeamMemberRoleDto
{
    public function __construct(
        public int $userId,
        public string $role,
    ) {}
}
