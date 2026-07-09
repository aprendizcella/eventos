<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Payments;

final readonly class CommissionCalculation
{
    public function __construct(
        public int $grossAmount,
        public int $percentageAmount,
        public int $fixedAmount,
        public int $commissionAmount,
        public int $netAmount,
        public string $currency,
    ) {}
}
