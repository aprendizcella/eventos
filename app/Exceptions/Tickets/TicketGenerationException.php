<?php

declare(strict_types=1);

namespace App\Exceptions\Tickets;

use Exception;

final class TicketGenerationException extends Exception
{
    public static function collisionLimitExceeded(int $maxTries): self
    {
        return new self("Could not generate a unique ticket code after {$maxTries} attempts.");
    }
}
