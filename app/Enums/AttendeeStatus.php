<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendeeStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case CheckedIn = 'checked_in';
}
