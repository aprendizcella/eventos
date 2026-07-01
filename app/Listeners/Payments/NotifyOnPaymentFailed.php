<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Events\Payments\PaymentFailed;
use Illuminate\Support\Facades\Log;

final class NotifyOnPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        Log::warning('Payment failed.', [
            'payment_id' => $event->payment->payment_id,
            'ticket_order_id' => $event->payment->ticket_order_id,
            'amount' => $event->payment->amount,
        ]);
    }
}
