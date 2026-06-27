<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Organizers;

final readonly class AddTeamMemberDto
{
    public function __construct(
        public int $userId,
        public string $role,
    ) {}
}
