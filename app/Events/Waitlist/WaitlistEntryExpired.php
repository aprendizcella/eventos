<?php

declare(strict_types=1);

namespace App\Events\Waitlist;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WaitlistEntryExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $waitlistEntryId,
        public int $productPriceId,
        public int $eventId,
    ) {}
}
