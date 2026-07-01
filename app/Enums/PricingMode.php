<?php

declare(strict_types=1);

namespace App\Enums;

enum PricingMode: string
{
    case Free = 'free';
    case Paid = 'paid';
    case Donation = 'donation';
}
