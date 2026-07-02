<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Tickets;

use App\Models\Attendee;

final readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $status,
        public string $message,
        public ?Attendee $attendee = null,
        public ?string $checkedInAt = null,
        public ?int $activeCheckInId = null,
    ) {}
}
