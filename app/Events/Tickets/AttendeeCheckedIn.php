<?php

declare(strict_types=1);

namespace App\Events\Tickets;

use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttendeeCheckedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Attendee $attendee,
        public ActiveCheckIn $activeCheckIn,
    ) {}
}
