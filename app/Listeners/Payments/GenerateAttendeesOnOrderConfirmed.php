<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Actions\Tickets\GenerateAttendeesAction;
use App\Events\Payments\PaymentCompleted;
use App\Jobs\Payments\SendTicketEmailJob;

final readonly class GenerateAttendeesOnOrderConfirmed
{
    public function __construct(
        private GenerateAttendeesAction $generateAttendeesAction,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $order = $event->payment->ticketOrder;

        if ($order === null) {
            return;
        }

        // 1. Generación de asistentes ligera de forma síncrona
        ($this->generateAttendeesAction)($order);

        // 2. Despacho del job pesado en cola después de hacer commit a la transacción
        dispatch(new SendTicketEmailJob($order->ticket_order_id))->afterCommit();
    }
}
