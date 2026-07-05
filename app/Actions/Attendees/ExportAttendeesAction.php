<?php

declare(strict_types=1);

namespace App\Actions\Attendees;

use App\Models\Attendee;
use App\Models\Event;
use Closure;
use Illuminate\Support\Facades\DB;

final readonly class ExportAttendeesAction
{
    /**
     * Retorna un callback de streaming para exportar asistentes en CSV.
     *
     * @param  array{product_price_id?: ?int, attendee_status?: ?string, check_in_status?: ?string}  $filters
     */
    public function __invoke(Event $event, array $filters = []): Closure
    {
        return function () use ($event, $filters): void {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            // UTF-8 BOM para soporte correcto de caracteres especiales en Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($file, [
                'Ticket Code',
                'First Name',
                'Last Name',
                'Email',
                'Status',
                'Ticket Type',
                'Order Reference',
                'Checked In',
                'Custom Answers (JSON)',
            ],
                escape: '\\');

            // Query optimizada usando el scope unificado para filtros consistentes.
            // La presencia del check-in se resuelve con una subconsulta SELECT EXISTS
            // para evitar problemas N+1 y prevenir duplicación de filas.
            $query = Attendee::query()
                ->select([
                    'attendee.*',
                    DB::raw('EXISTS(
                        SELECT 1 FROM active_check_in
                        WHERE active_check_in.attendee_id = attendee.attendee_id
                    ) as is_checked_in'),
                ])
                ->forEventSegment($event->event_id, $filters)
                ->with(['ticketOrderItem.productPrice', 'ticketOrder']);

            // Procesar en chunks usando lazyById (100 a la vez) para un Eager Loading seguro y consumo de memoria optimizado
            foreach ($query->lazyById(100, 'attendee_id') as $attendee) {
                fputcsv($file, [
                    $attendee->unique_code,
                    $attendee->first_name,
                    $attendee->last_name,
                    $attendee->email,
                    $attendee->status->value,
                    $attendee->ticketOrderItem->productPrice->name ?? '—',
                    $attendee->ticketOrder->order_reference ?? '—',
                    $attendee->getAttribute('is_checked_in') ? 'Yes' : 'No',
                    json_encode($attendee->custom_answers ?? [], JSON_UNESCAPED_UNICODE),
                ],
                    escape: '\\');
            }

            fclose($file);
        };
    }
}
