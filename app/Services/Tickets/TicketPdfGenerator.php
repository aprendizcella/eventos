<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class TicketPdfGenerator
{
    public function __construct(
        private readonly QrCodeGenerator $qrCodeGenerator,
    ) {}

    /**
     * Genera el PDF con las entradas de los asistentes y sus QRs.
     *
     * @param  Collection<int, \App\Models\Attendee>|array<int, \App\Models\Attendee>  $attendees
     */
    public function generateForAttendees(Collection|array $attendees): string
    {
        $qrCodes = [];

        foreach ($attendees as $attendee) {
            $qrCodes[$attendee->attendee_id] = $this->qrCodeGenerator->generateBase64DataUri($attendee->unique_code);
        }

        $pdf = Pdf::loadView('tickets.pdf', [
            'attendees' => $attendees,
            'qrCodes' => $qrCodes,
        ]);

        // Retorna el contenido del archivo PDF en bytes
        return $pdf->output();
    }
}
