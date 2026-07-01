<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Events\Payments\PaymentCompleted;
use Illuminate\Support\Facades\Log;

final class NotifyOnPaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        Log::info('Payment completed successfully.', [
            'payment_id' => $event->payment->payment_id,
            'ticket_order_id' => $event->payment->ticket_order_id,
            'amount' => $event->payment->amount,
        ]);
    }
}
