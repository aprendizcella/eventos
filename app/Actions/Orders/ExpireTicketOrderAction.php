<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Actions\Waitlist\RollbackWaitlistReservationAction;
use App\Enums\TicketOrderStatus;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;

final readonly class ExpireTicketOrderAction
{
    public function __construct(
        private RollbackWaitlistReservationAction $rollbackWaitlistReservationAction,
    ) {}

    public function __invoke(TicketOrder $order): TicketOrder
    {
        return DB::transaction(function () use ($order): TicketOrder {
            $order->update([
                'status' => TicketOrderStatus::Expired,
                'reserved_until' => null,
            ]);

            if ($order->waitlist_entry_id !== null && $order->waitlistEntry !== null) {
                ($this->rollbackWaitlistReservationAction)($order->waitlistEntry);
            }

            activity()
                ->performedOn($order)
                ->useLog('ticket_order')
                ->log('expired');

            return $order->refresh();
        });
    }
}
