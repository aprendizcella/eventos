<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Admin;

use Carbon\CarbonInterface;

final readonly class AuditLogFilterDto
{
    public const array LOG_NAMES = ['auth', 'default', 'system'];

    public const array EVENTS = ['created', 'deleted', 'login', 'logout', 'updated'];

    public function __construct(
        public ?string $logName = null,
        public ?string $event = null,
        public ?CarbonInterface $dateFrom = null,
        public ?CarbonInterface $dateTo = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function isSafe(): bool
    {
        $hasValidLogName = $this->logName === null || in_array($this->logName, self::LOG_NAMES, true);
        $hasValidEvent = $this->event === null || in_array($this->event, self::EVENTS, true);
        $hasDatePair = ($this->dateFrom instanceof CarbonInterface) === ($this->dateTo instanceof CarbonInterface);
        $hasValidDateRange = !$this->dateFrom instanceof CarbonInterface
            || !$this->dateTo instanceof CarbonInterface
            || ($this->dateFrom->lessThanOrEqualTo($this->dateTo)
                && $this->dateFrom->diffInDays($this->dateTo) <= 90);

        return $hasValidLogName && $hasValidEvent && $hasDatePair && $hasValidDateRange;
    }
}
