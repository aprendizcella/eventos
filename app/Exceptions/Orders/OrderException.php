<?php

declare(strict_types=1);

namespace App\Exceptions\Orders;

use RuntimeException;

final class OrderException extends RuntimeException
{
    public static function invalidStatus(string $message): self
    {
        return new self($message);
    }

    public static function stockDepleted(string $message): self
    {
        return new self($message);
    }

    public static function invalidSelection(string $message): self
    {
        return new self($message);
    }
}
