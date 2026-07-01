<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Enums\PaymentStatus;
use App\Events\Payments\RefundProcessed;
use Illuminate\Support\Facades\Log;

final class ReleaseStockOnRefund
{
    public function handle(RefundProcessed $event): void
    {
        $refund = $event->refund;
        $payment = $refund->payment;

        if ($payment === null) {
            return;
        }

        // Solo liberamos el stock si el pago se ha reembolsado en su totalidad
        if ($payment->status === PaymentStatus::Refunded) {
            $order = $payment->ticketOrder;

            if ($order === null) {
                return;
            }

            foreach ($order->items as $item) {
                if ($item->product_price_id !== null && $item->productPrice !== null) {
                    $item->productPrice->decrement('quantity_sold', $item->quantity);
                }
            }

            Log::info('Order stock released due to full refund.', [
                'ticket_order_id' => $order->ticket_order_id,
                'payment_id' => $payment->payment_id,
            ]);
        }
    }
}
