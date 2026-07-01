<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketOrderStatus: string
{
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';
}
