<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AuditLogQueryException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Database query failure occurred during audit presentation.');
    }
}
