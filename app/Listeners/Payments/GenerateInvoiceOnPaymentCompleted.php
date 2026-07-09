<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Actions\Payments\GenerateInvoiceAction;
use App\Events\Payments\PaymentCompleted;

final readonly class GenerateInvoiceOnPaymentCompleted
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoiceAction,
    ) {}

    /**
     * Generate an invoice when a payment is completed.
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $order = $payment->ticketOrder;

        if ($order === null) {
            return;
        }

        // Idempotency: skip if an invoice already exists for this payment
        if ($order->invoice()->exists()) {
            return;
        }

        ($this->generateInvoiceAction)($order, $payment);
    }
}
