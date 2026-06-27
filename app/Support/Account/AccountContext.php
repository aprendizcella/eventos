<?php

declare(strict_types=1);

namespace App\Support\Account;

final readonly class AccountContext
{
    public function __construct(
        public string $roleLabel,
        public string $organizerLabel,
    ) {}
}
