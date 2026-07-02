<?php

declare(strict_types=1);

namespace App\Exceptions\Tickets;

use Exception;

final class CheckInException extends Exception
{
    public static function validationFailed(string $message): self
    {
        return new self($message);
    }

    public static function notFound(): self
    {
        return new self('Attendee record not found.');
    }

    public static function activeRecordNotFound(): self
    {
        return new self('No active check-in record found to revert.');
    }

    public static function orderNotFound(): self
    {
        return new self('Ticket order not found.');
    }
}
