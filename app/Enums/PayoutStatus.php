<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Processed = 'processed';
    case Reversed = 'reversed';
    case Failed = 'failed';
}
