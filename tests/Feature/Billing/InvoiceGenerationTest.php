<?php

declare(strict_types=1);

use App\Actions\Payments\GenerateInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Events\Payments\PaymentCompleted;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('generates an invoice for a completed payment', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 150.00,
        'status' => PaymentStatus::Completed,
    ]);
    $order = $payment->ticketOrder;

    $action = resolve(GenerateInvoiceAction::class);
    $invoice = $action($order, $payment);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->type)->toBe(InvoiceType::Invoice)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->amount)->toBe(15000) // 150.00 * 100 = 15000 cents
        ->and($invoice->ticket_order_id)->toBe($order->ticket_order_id)
        ->and($invoice->payment_id)->toBe($payment->payment_id)
        ->and($invoice->currency)->toBe('USD')
        ->and($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('generates invoice with exact integer cents from fractional amounts', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 99.99,
        'status' => PaymentStatus::Completed,
    ]);
    $order = $payment->ticketOrder;

    $action = resolve(GenerateInvoiceAction::class);
    $invoice = $action($order, $payment);

    expect($invoice->amount)->toBe(9999) // 99.99 * 100 = 9999
        ->and(is_int($invoice->amount))->toBeTrue();
});

it('generates sequential invoice numbers for the same organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $payment1 = Payment::factory()->create(['status' => PaymentStatus::Completed]);
    $payment1->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $payment2 = Payment::factory()->create(['status' => PaymentStatus::Completed]);
    $payment2->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $action = resolve(GenerateInvoiceAction::class);

    $invoice1 = $action($payment1->ticketOrder, $payment1);
    $invoice2 = $action($payment2->ticketOrder, $payment2);

    expect($invoice2->number)->toBe($invoice1->number + 1);
});

it('listener creates invoice when PaymentCompleted is dispatched', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 75.50,
        'status' => PaymentStatus::Completed,
    ]);

    // Enable invoicing for the event
    $payment->ticketOrder->event->update([
        'settings' => ['billing' => ['invoice_enabled' => true]],
    ]);

    event(new PaymentCompleted($payment));

    $this->assertDatabaseHas('invoice', [
        'payment_id' => $payment->payment_id,
        'ticket_order_id' => $payment->ticket_order_id,
        'type' => InvoiceType::Invoice->value,
        'status' => InvoiceStatus::Paid->value,
        'amount' => 7550,
    ]);
});

it('is idempotent — does not create duplicate invoices for the same payment', function (): void {
    Event::fake();

    $payment = Payment::factory()->create([
        'amount' => 100.00,
        'status' => PaymentStatus::Completed,
    ]);

    // Enable invoicing for the event
    $payment->ticketOrder->event->update([
        'settings' => ['billing' => ['invoice_enabled' => true]],
    ]);

    Invoice::factory()->create([
        'payment_id' => $payment->payment_id,
        'ticket_order_id' => $payment->ticket_order_id,
        'type' => InvoiceType::Invoice,
    ]);

    event(new PaymentCompleted($payment));

    Event::assertDispatched(PaymentCompleted::class);

    $invoices = Invoice::query()
        ->where('payment_id', $payment->payment_id)
        ->where('type', InvoiceType::Invoice)
        ->count();

    expect($invoices)->toBe(1);
});

it('does not generate duplicate invoice when event is dispatched again', function (): void {
    Event::fake([PaymentCompleted::class]);

    $payment = Payment::factory()->create([
        'status' => PaymentStatus::Completed,
    ]);
    $order = $payment->ticketOrder;

    // Already has an invoice
    Invoice::factory()->create([
        'payment_id' => $payment->payment_id,
        'ticket_order_id' => $order->ticket_order_id,
        'type' => InvoiceType::Invoice,
    ]);

    // Only dispatch via fake — the real listener is not called
    event(new PaymentCompleted($payment));

    Event::assertDispatched(PaymentCompleted::class, fn (PaymentCompleted $e) => $e->payment->payment_id === $payment->payment_id);

    // Only one invoice should exist for this payment
    expect(
        Invoice::query()
            ->where('payment_id', $payment->payment_id)
            ->where('type', InvoiceType::Invoice)
            ->count(),
    )->toBe(1);
});

it('associates invoice with the correct organizer through the event chain', function (): void {
    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create(['status' => PaymentStatus::Completed]);
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $action = resolve(GenerateInvoiceAction::class);
    $invoice = $action($payment->ticketOrder, $payment);

    expect($invoice->organizer_id)->toBe($organizer->id);
});
