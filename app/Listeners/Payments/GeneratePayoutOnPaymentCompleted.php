<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Actions\Payments\CreatePayoutAction;
use App\Events\Payments\PaymentCompleted;

final readonly class GeneratePayoutOnPaymentCompleted
{
    public function __construct(
        private CreatePayoutAction $createPayoutAction,
    ) {}

    /**
     * Generate an internal payout record when a payment is completed.
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $order = $payment->ticketOrder;

        if ($order === null || $order->event === null) {
            return;
        }

        $invoice = $order->invoice;

        if ($invoice === null) {
            return;
        }

        ($this->createPayoutAction)($invoice);
    }
}
