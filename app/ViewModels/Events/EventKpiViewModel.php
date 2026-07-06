<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Enums\AttendeeStatus;
use App\Enums\TicketOrderStatus;
use App\Enums\WaitlistStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Payment;
use App\Models\ProductPrice;
use App\Models\WaitlistEntry;
use App\ViewModels\ViewModel;
use Illuminate\Support\Facades\DB;

final class EventKpiViewModel extends ViewModel
{
    public function __construct(public Event $event) {}

    /**
     * Calcula los ingresos netos totales de órdenes completadas descontando reembolsos.
     */
    public function netRevenue(): float
    {
        $completedPayments = Payment::query()
            ->whereHas('ticketOrder', function ($query) {
                $query->where('event_id', $this->event->event_id)
                    ->where('status', TicketOrderStatus::Completed->value);
            })
            ->with('refunds')
            ->get();

        return (float) $completedPayments->sum(fn (Payment $payment) => $payment->amount - $payment->getTotalRefundedAmount());
    }

    /**
     * Cantidad de asistentes activos.
     */
    public function activeAttendeesCount(): int
    {
        return Attendee::query()
            ->whereHas('ticketOrder', function ($query) {
                $query->where('event_id', $this->event->event_id);
            })
            ->where('status', AttendeeStatus::Active->value)
            ->count();
    }

    /**
     * Calcula el porcentaje de check-ins sobre asistentes activos.
     */
    public function checkInRate(): float
    {
        $activeCount = $this->activeAttendeesCount();

        if ($activeCount === 0) {
            return 0.0;
        }

        $checkedInCount = Attendee::query()
            ->whereHas('ticketOrder', function ($query) {
                $query->where('event_id', $this->event->event_id);
            })
            ->where('status', AttendeeStatus::Active->value)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('active_check_in')
                    ->whereColumn('active_check_in.attendee_id', 'attendee.attendee_id');
            })
            ->count();

        return round(($checkedInCount / $activeCount) * 100, 1);
    }

    /**
     * Obtiene el ratio de capacidad utilizada o 'Ilimitado'.
     */
    public function capacityUtilization(): string
    {
        $activeCount = $this->activeAttendeesCount();

        // Obtener la capacidad sumando la capacidad de las tarifas
        $totalCapacity = ProductPrice::query()
            ->join('product', 'product_price.product_id', '=', 'product.product_id')
            ->where('product.event_id', $this->event->event_id)
            ->sum('product_price.capacity');

        // Si alguna tarifa tiene capacidad ilimitada (capacidad null), no es un límite cerrado
        $hasUnlimited = ProductPrice::query()
            ->join('product', 'product_price.product_id', '=', 'product.product_id')
            ->where('product.event_id', $this->event->event_id)
            ->whereNull('product_price.capacity')
            ->exists();

        if ($hasUnlimited || $totalCapacity == 0) {
            return __('Unlimited');
        }

        $rate = round(($activeCount / $totalCapacity) * 100, 1);

        return "{$activeCount} / {$totalCapacity} ({$rate}%)";
    }

    /**
     * Solicitudes en lista de espera activas (Waiting o Notified).
     */
    public function activeWaitlistCount(): int
    {
        return WaitlistEntry::query()
            ->where('event_id', $this->event->event_id)
            ->whereIn('status', [WaitlistStatus::Waiting->value, WaitlistStatus::Notified->value])
            ->count();
    }

    /**
     * Ventas diarias de los últimos 30 días para graficar.
     * Retorna un array ordenado por fecha con la suma neta diaria.
     *
     * @return array<string, float>
     */
    public function salesHistory(): array
    {
        $startDate = \Illuminate\Support\Facades\Date::today()->subDays(29);

        $dailyNetByDate = DB::table('payment')
            ->join('ticket_order', 'payment.ticket_order_id', '=', 'ticket_order.ticket_order_id')
            ->leftJoin(DB::raw('(
                SELECT payment_id, SUM(amount) as refunded
                FROM refund
                WHERE status = \'completed\'
                GROUP BY payment_id
            ) refund_summary'), 'refund_summary.payment_id', '=', 'payment.payment_id')
            ->where('ticket_order.event_id', $this->event->event_id)
            ->where('ticket_order.status', TicketOrderStatus::Completed->value)
            ->where('payment.created_at', '>=', $startDate)
            ->selectRaw('DATE(payment.created_at) as day, SUM(payment.amount - COALESCE(refund_summary.refunded, 0)) as net')
            ->groupBy('day')
            ->pluck('net', 'day');
        $history = [];
        for ($i = 0; $i < 30; $i++) {
            $dateStr = $startDate->copy()->addDays($i)->format('Y-m-d');
            $history[$dateStr] = (float) ($dailyNetByDate[$dateStr] ?? 0);
        }

        return $history;
    }
}
