<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use App\Mail\OrderConfirmedMail;
use App\Models\TicketOrder;
use App\Services\Tickets\TicketPdfGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Throwable;

/**
 * This job receives the full order ID in its constructor and queries by it directly.
 * It does NOT need tenant context — it processes the exact order it was given.
 */
final class SendTicketEmailJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    /**
     * El número de veces que se puede intentar el trabajo.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos que se debe esperar antes de reintentar el trabajo.
     *
     * @var array<int, int>
     */
    public $backoff = [60, 300, 900];

    /**
     * El número de segundos que el trabajo puede ejecutarse antes de expirar.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TicketPdfGenerator $pdfGenerator): void
    {
        // 1. Semáforo y Claim Atómico con TTL (15 minutos)
        $claimed = TicketOrder::query()
            ->where('ticket_order_id', $this->orderId)
            ->whereNull('tickets_sent_at')
            ->where(function ($query) {
                $query->whereNull('tickets_processing_at')
                    ->orWhere('tickets_processing_at', '<', now()->subMinutes(15));
            })
            ->update(['tickets_processing_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
            /** @var TicketOrder $order */
            $order = TicketOrder::query()
                ->with(['attendees', 'event'])
                ->findOrFail($this->orderId);

            // 2. Generación del PDF con las entradas de forma aislada
            $pdfContent = $pdfGenerator->generateForAttendees($order->attendees);

            // 3. Envío de Correo con PDF Adjunto
            Mail::to($order->email)->send(new OrderConfirmedMail($order, $pdfContent));

            // 4. Actualización Atómica Final al Éxito
            TicketOrder::query()
                ->where('ticket_order_id', $this->orderId)
                ->update([
                    'tickets_sent_at' => now(),
                    'tickets_processing_at' => null,
                ]);
        } catch (Throwable $e) {
            // Liberar el semáforo en caso de excepción para permitir reintentos controlados por el backoff
            TicketOrder::query()
                ->where('ticket_order_id', $this->orderId)
                ->update(['tickets_processing_at' => null]);

            throw $e;
        }
    }
}
