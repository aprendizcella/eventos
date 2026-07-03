<?php

declare(strict_types=1);

namespace App\Exceptions\Waitlist;

use RuntimeException;

final class WaitlistException extends RuntimeException
{
    public static function alreadyRegistered(string $message): self
    {
        return new self($message);
    }
}
