<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\TicketOrderStatus;
use App\Exceptions\Orders\OrderException;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmTicketOrderAction
{
    public function __construct(
        private \App\Actions\Waitlist\ConvertWaitlistEntryAction $convertWaitlistEntryAction,
    ) {}

    public function __invoke(TicketOrder $order): TicketOrder
    {
        if ($order->status !== TicketOrderStatus::Reserved) {
            throw OrderException::invalidStatus(__('Only reserved orders can be confirmed.'));
        }

        if ($order->reserved_until !== null && now()->greaterThan($order->reserved_until)) {
            throw OrderException::invalidStatus(__('Order reservation has expired.'));
        }

        return DB::transaction(function () use ($order): TicketOrder {
            // Confirmar estado
            $order->update([
                'status' => TicketOrderStatus::Completed,
                'reserved_until' => null,
            ]);

            if ($order->waitlist_entry_id !== null && $order->waitlistEntry !== null) {
                ($this->convertWaitlistEntryAction)($order->waitlistEntry);
            }

            // Consolidar el stock
            $this->consolidateStock($order);

            // Incrementar uso del cupón si se aplicó uno
            if ($order->promo_code_id !== null && $order->promoCode !== null) {
                $order->promoCode->increment('uses_count');
            }

            activity()
                ->performedOn($order)
                ->useLog('ticket_order')
                ->log('completed');

            return $order->refresh();
        });
    }

    private function consolidateStock(TicketOrder $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_price_id !== null && $item->productPrice !== null) {
                $item->productPrice->increment('quantity_sold', $item->quantity);
            }
        }
    }
}
