<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Refund;
use RoundingMode;

final readonly class AdjustPayoutAction
{
    /**
     * Adjust or reverse a payout when a refund is processed.
     */
    public function __invoke(Refund $refund): ?Payout
    {
        $payout = $this->findPayoutForRefund($refund);

        if (!$payout instanceof Payout) {
            return null;
        }

        $refundAmount = (int) round($refund->amount * 100);

        return $refundAmount >= $payout->gross_amount
            ? $this->reverse($payout, $refund)
            : $this->adjust($payout, $refundAmount, $refund);
    }

    private function findPayoutForRefund(Refund $refund): ?Payout
    {
        $payment = $refund->payment;

        if ($payment === null || $payment->ticketOrder === null) {
            return null;
        }

        $invoice = Invoice::query()
            ->where('ticket_order_id', $payment->ticketOrder->getKey())
            ->where('payment_id', $payment->getKey())
            ->oldest('invoice_id')
            ->first();

        if ($invoice === null) {
            return null;
        }

        return $invoice->payout;
    }

    private function reverse(Payout $payout, Refund $refund): Payout
    {
        $payout->update([
            'refund_id' => $refund->getKey(),
            'status' => PayoutStatus::Reversed,
            'reversed_at' => now(),
        ]);

        return $payout;
    }

    private function adjust(Payout $payout, int $refundAmount, Refund $refund): Payout
    {
        $remainingGross = $payout->gross_amount - $refundAmount;

        $commissionRatio = $payout->gross_amount > 0
            ? $payout->commission_amount / $payout->gross_amount
            : 0;

        $adjustedCommission = (int) round($remainingGross * $commissionRatio, 0, RoundingMode::HalfAwayFromZero);
        $adjustedNet = max(0, $remainingGross - $adjustedCommission);

        $payout->update([
            'refund_id' => $refund->getKey(),
            'gross_amount' => $remainingGross,
            'commission_amount' => $adjustedCommission,
            'net_amount' => $adjustedNet,
            'status' => PayoutStatus::Ready,
        ]);

        return $payout;
    }
}
