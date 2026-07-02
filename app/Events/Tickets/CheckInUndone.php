<?php

declare(strict_types=1);

namespace App\Events\Tickets;

use App\Models\Attendee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CheckInUndone
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Attendee $attendee,
    ) {}
}
