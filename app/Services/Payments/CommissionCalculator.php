<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\DataTransferObjects\Organizers\BillingSettings;
use App\DataTransferObjects\Payments\CommissionCalculation;
use RoundingMode;

final class CommissionCalculator
{
    public function calculate(int $grossAmount, BillingSettings $billingSettings, string $currency = 'USD'): CommissionCalculation
    {
        $percentageAmount = 0;

        if ($billingSettings->platformFeePercentage !== null) {
            $percentageAmount = (int) round(($grossAmount * $billingSettings->platformFeePercentage) / 10000, 0, RoundingMode::HalfAwayFromZero);
        }

        $fixedAmount = $billingSettings->platformFeeFixed ?? 0;
        $commissionAmount = min($grossAmount, $percentageAmount + $fixedAmount);
        $netAmount = max(0, $grossAmount - $commissionAmount);

        return new CommissionCalculation(
            grossAmount: $grossAmount,
            percentageAmount: $percentageAmount,
            fixedAmount: $fixedAmount,
            commissionAmount: $commissionAmount,
            netAmount: $netAmount,
            currency: $currency,
        );
    }
}
