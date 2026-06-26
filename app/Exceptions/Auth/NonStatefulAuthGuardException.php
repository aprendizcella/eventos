<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use RuntimeException;

final class NonStatefulAuthGuardException extends RuntimeException
{
    public static function forGuard(string $guardClass): self
    {
        return new self(
            'The default auth guard must be stateful for the auth Actions, received ['.$guardClass.'].',
        );
    }
}
