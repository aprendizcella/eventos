<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Auth;

final readonly class ResetPasswordDto
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
