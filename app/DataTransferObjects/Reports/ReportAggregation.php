<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Reports;

final readonly class ReportAggregation
{
    public function __construct(
        public string $currency,
        public int $totalRevenue = 0,
        public int $totalTax = 0,
        public int $totalFees = 0,
        public int $invoiceCount = 0,
        public int $totalGross = 0,
        public int $totalCommission = 0,
        public int $totalNet = 0,
        public int $payoutCount = 0,
    ) {}
}
