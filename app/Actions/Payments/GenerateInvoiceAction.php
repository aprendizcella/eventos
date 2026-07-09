<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Exceptions\Invoices\InvoiceGenerationException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TicketOrder;

final readonly class GenerateInvoiceAction
{
    public function __construct(
        private GenerateInvoiceNumberAction $generateInvoiceNumberAction,
    ) {}

    /**
     * Generate an invoice for a paid order.
     */
    public function __invoke(TicketOrder $ticketOrder, Payment $payment): Invoice
    {
        $event = $ticketOrder->event;

        if ($event === null) {
            throw new InvoiceGenerationException('Cannot generate invoice: order has no associated event.');
        }

        $organizer = $event->organizer;

        if ($organizer === null) {
            throw new InvoiceGenerationException('Cannot generate invoice: event has no associated organizer.');
        }

        $numbering = ($this->generateInvoiceNumberAction)($organizer, InvoiceType::Invoice);

        return Invoice::query()->create([
            'organizer_id' => $organizer->getKey(),
            'ticket_order_id' => $ticketOrder->getKey(),
            'payment_id' => $payment->getKey(),
            'refund_id' => null,
            'type' => InvoiceType::Invoice,
            'status' => InvoiceStatus::Paid,
            'year' => $numbering['year'],
            'number' => $numbering['number'],
            'invoice_number' => $numbering['invoice_number'],
            'amount' => (int) round($payment->amount * 100),
            'currency' => $payment->currency,
        ]);
    }
}
