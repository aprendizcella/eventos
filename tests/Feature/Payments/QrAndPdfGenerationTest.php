<?php

declare(strict_types=1);

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketOrder;
use App\Services\Tickets\QrCodeGenerator;
use App\Services\Tickets\TicketPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('generates a valid QR code in SVG and Base64 Data URI format', function (): void {
    $generator = resolve(QrCodeGenerator::class);

    $svg = $generator->generateSvg('TKT-TEST1234');
    expect($svg)->toContain('<svg')
        ->toContain('</svg>');

    $base64 = $generator->generateBase64DataUri('TKT-TEST1234');
    expect($base64)->toStartWith('data:image/svg+xml;base64,');
});

it('renders ticket PDF correctly from layout views', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);

    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'unique_code' => 'TKT-PDFTEST',
    ]);

    $pdfGenerator = resolve(TicketPdfGenerator::class);
    $pdfData = $pdfGenerator->generateForAttendees([$attendee]);

    expect($pdfData)->not->toBeEmpty();
    // Validar cabecera mágica de firma PDF
    expect(str_starts_with($pdfData, '%PDF-'))->toBeTrue();
});
