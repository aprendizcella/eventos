<?php

declare(strict_types=1);

namespace App\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    case Notified = 'notified';
    case Reserved = 'reserved';
    case Expired = 'expired';
    case Converted = 'converted';
}
