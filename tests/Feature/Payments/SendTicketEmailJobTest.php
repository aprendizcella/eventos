<?php

declare(strict_types=1);

use App\Actions\Tickets\GenerateAttendeesAction;
use App\Enums\ProductType;
use App\Jobs\Payments\SendTicketEmailJob;
use App\Mail\OrderConfirmedMail;
use App\Models\Product;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use App\Services\Tickets\TicketPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('claims and processes order tickets sending email exactly once', function (): void {
    Mail::fake();

    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'tickets_sent_at' => null,
        'tickets_processing_at' => null,
    ]);

    $product = Product::factory()->create(['type' => ProductType::Ticket]);
    TicketOrderItem::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'product_id' => $product->product_id,
        'quantity' => 1,
    ]);

    // Generar los asistentes primero
    resolve(GenerateAttendeesAction::class)($order);

    // Ejecutar el Job
    $job = new SendTicketEmailJob($order->ticket_order_id);
    $job->handle(resolve(TicketPdfGenerator::class));

    // Verificar que se envió el correo
    Mail::assertSent(OrderConfirmedMail::class, fn ($mail) => $mail->hasTo($order->email) && $mail->order->ticket_order_id === $order->ticket_order_id);

    // Verificar que la orden quedó marcada como enviada y liberada
    $order->refresh();
    expect($order->tickets_sent_at)->not->toBeNull()
        ->and($order->tickets_processing_at)->toBeNull();
});

it('prevents duplicate processing concurrently (atomic claim)', function (): void {
    Mail::fake();

    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'tickets_sent_at' => null,
        'tickets_processing_at' => null,
    ]);

    // Establecer manualmente como que ya se está procesando
    $order->tickets_processing_at = now();
    $order->save();

    $job = new SendTicketEmailJob($order->ticket_order_id);
    $job->handle(resolve(TicketPdfGenerator::class));

    // No se debe enviar email porque la reclamación atómica debió fallar (devolvió 0)
    Mail::assertNotSent(OrderConfirmedMail::class);
});

it('releases lock if job execution fails', function (): void {
    Mail::fake();

    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'tickets_sent_at' => null,
        'tickets_processing_at' => null,
    ]);

    // Provocar un error inyectando un generador corrupto
    $badPdfGenerator = Mockery::mock(TicketPdfGenerator::class);
    $badPdfGenerator->shouldReceive('generateForAttendees')
        ->andThrow(new RuntimeException('PDF generation failed!')); // @phpstan-ignore-line

    $job = new SendTicketEmailJob($order->ticket_order_id);

    try {
        $job->handle($badPdfGenerator); // @phpstan-ignore-line
    } catch (RuntimeException) {
        // expected
    }

    // El semáforo debe haberse liberado a null tras la excepción
    $order->refresh();
    expect($order->tickets_processing_at)->toBeNull();
});
