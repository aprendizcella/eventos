<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Auth;

final readonly class RequestPasswordResetDto
{
    public function __construct(
        public string $email,
    ) {}
}
