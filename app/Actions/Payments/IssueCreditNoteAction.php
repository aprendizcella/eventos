<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Exceptions\Invoices\InvoiceGenerationException;
use App\Models\Invoice;
use App\Models\Refund;

final readonly class IssueCreditNoteAction
{
    public function __construct(
        private GenerateInvoiceNumberAction $generateInvoiceNumberAction,
    ) {}

    /**
     * Issue a credit note for a processed refund.
     */
    public function __invoke(Refund $refund): Invoice
    {
        $payment = $refund->payment;

        if ($payment === null || $payment->ticketOrder === null) {
            throw new InvoiceGenerationException('Cannot issue credit note: refund has no associated payment or order.');
        }

        $ticketOrder = $payment->ticketOrder;

        if ($ticketOrder->event === null) {
            throw new InvoiceGenerationException('Cannot issue credit note: order has no associated event.');
        }

        $organizer = $ticketOrder->event->organizer;

        if ($organizer === null) {
            throw new InvoiceGenerationException('Cannot issue credit note: event has no associated organizer.');
        }

        $numbering = ($this->generateInvoiceNumberAction)($organizer, InvoiceType::CreditNote);

        return Invoice::query()->create([
            'organizer_id' => $organizer->getKey(),
            'ticket_order_id' => $ticketOrder->getKey(),
            'payment_id' => $payment->getKey(),
            'refund_id' => $refund->getKey(),
            'type' => InvoiceType::CreditNote,
            'status' => InvoiceStatus::Issued,
            'year' => $numbering['year'],
            'number' => $numbering['number'],
            'invoice_number' => $numbering['invoice_number'],
            'amount' => (int) round($refund->amount * 100),
            'currency' => $payment->currency,
        ]);
    }
}
