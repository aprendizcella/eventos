<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class LastAdminCannotBeRemovedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Cannot remove the last admin of an organizer.');
    }
}
