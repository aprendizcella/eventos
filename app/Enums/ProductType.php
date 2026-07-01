<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case Ticket = 'ticket';
    case Addon = 'addon';
    case Merchandise = 'merchandise';
}
