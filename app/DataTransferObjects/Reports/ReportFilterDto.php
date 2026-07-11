<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Reports;

use Carbon\CarbonInterface;

final readonly class ReportFilterDto
{
    public function __construct(
        public ?CarbonInterface $dateFrom = null,
        public ?CarbonInterface $dateTo = null,
        public ?string $currency = null,
        public ?int $organizerId = null,
        public ?int $eventId = null,
    ) {}

    /**
     * Create a default filter covering the last 90 days.
     */
    public static function default(): self
    {
        return new self(
            dateFrom: now()->subDays(90)->startOfDay(),
            dateTo: now()->endOfDay(),
        );
    }
}
