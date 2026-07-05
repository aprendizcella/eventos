<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Notifications;

final readonly class SendBulkMessageDto
{
    public function __construct(
        public int $eventId,
        public string $subject,
        public string $body,
        public ?int $productPriceId = null,
        public ?string $attendeeStatus = null,
        public ?string $checkInStatus = null,
    ) {}
}
