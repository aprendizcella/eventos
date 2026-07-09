<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\DataTransferObjects\Organizers\BillingSettings;
use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Payout;
use App\Services\Payments\CommissionCalculator;

final readonly class CreatePayoutAction
{
    public function __construct(
        private CommissionCalculator $commissionCalculator,
    ) {}

    /**
     * Create an internal payout record for a paid invoice.
     */
    public function __invoke(Invoice $invoice): ?Payout
    {
        if ($invoice->payout()->exists()) {
            return $invoice->payout;
        }

        $billingSettings = $this->resolveBillingSettings($invoice);

        if (!$billingSettings instanceof BillingSettings || $invoice->organizer === null) {
            return null;
        }

        $calculation = $this->commissionCalculator->calculate(
            $invoice->amount,
            $billingSettings,
            $invoice->currency,
        );

        return Payout::query()->create([
            'organizer_id' => $invoice->organizer->getKey(),
            'invoice_id' => $invoice->getKey(),
            'refund_id' => null,
            'gross_amount' => $calculation->grossAmount,
            'commission_amount' => $calculation->commissionAmount,
            'net_amount' => $calculation->netAmount,
            'currency' => $calculation->currency,
            'status' => PayoutStatus::Ready,
        ]);
    }

    private function resolveBillingSettings(Invoice $invoice): ?BillingSettings
    {
        $organizer = $invoice->organizer;

        if ($organizer === null) {
            return null;
        }

        /** @var array<string, mixed>|null $rawSettings */
        $rawSettings = $organizer->settings;
        $settings = is_array($rawSettings) ? $rawSettings : [];

        /** @var array<string, mixed> $billingData */
        $billingData = is_array($settings['billing'] ?? null) ? $settings['billing'] : [];

        $billingSettings = BillingSettings::fromArray($billingData);

        if ($billingSettings->platformFeePercentage === null && $billingSettings->platformFeeFixed === null) {
            return null;
        }

        return $billingSettings;
    }
}
