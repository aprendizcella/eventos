<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Actions\Payments\IssueCreditNoteAction;
use App\Events\Payments\RefundProcessed;
use App\Models\Invoice;

final readonly class IssueCreditNoteOnRefundProcessed
{
    public function __construct(
        private IssueCreditNoteAction $issueCreditNoteAction,
    ) {}

    /**
     * Issue a credit note when a refund is processed.
     */
    public function handle(RefundProcessed $event): void
    {
        $refund = $event->refund;
        $payment = $refund->payment;

        if ($payment === null || $payment->ticketOrder === null) {
            return;
        }

        // Idempotency: skip if a credit note already exists for this refund
        $existingCreditNote = Invoice::query()
            ->where('refund_id', $refund->getKey())
            ->where('type', \App\Enums\InvoiceType::CreditNote)
            ->exists();

        if ($existingCreditNote) {
            return;
        }

        ($this->issueCreditNoteAction)($refund);
    }
}
