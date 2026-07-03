<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\TicketOrderStatus;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;

final readonly class CancelTicketOrderAction
{
    public function __construct(
        private \App\Actions\Waitlist\RollbackWaitlistReservationAction $rollbackAction,
    ) {}

    public function __invoke(TicketOrder $order): TicketOrder
    {
        return DB::transaction(function () use ($order): TicketOrder {
            $oldStatus = $order->status;

            $order->update([
                'status' => TicketOrderStatus::Cancelled,
                'reserved_until' => null,
            ]);

            if ($order->waitlist_entry_id !== null && $order->waitlistEntry !== null) {
                ($this->rollbackAction)($order->waitlistEntry);
            }

            // Si la orden ya estaba completada, liberamos el stock consolidado
            if ($oldStatus === TicketOrderStatus::Completed) {
                $this->releaseStockAndCoupon($order);
            }

            activity()
                ->performedOn($order)
                ->useLog('ticket_order')
                ->log('cancelled');

            return $order->refresh();
        });
    }

    private function releaseStockAndCoupon(TicketOrder $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_price_id !== null && $item->productPrice !== null) {
                $item->productPrice->decrement('quantity_sold', $item->quantity);
            }
        }

        if ($order->promo_code_id !== null && $order->promoCode !== null) {
            $order->promoCode->decrement('uses_count');
        }
    }
}
