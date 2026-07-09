<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TicketOrder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an invoice with default values', function (): void {
    $invoice = Invoice::factory()->create();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->type)->toBeInstanceOf(InvoiceType::class)
        ->and($invoice->status)->toBeInstanceOf(InvoiceStatus::class)
        ->and($invoice->type)->toBe(InvoiceType::Invoice)
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->amount)->toBeInt()
        ->and($invoice->year)->toBe(now()->year)
        ->and($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('stores monetary amounts as exact integers (cents)', function (): void {
    $invoice = Invoice::factory()->create([
        'amount' => 1999,
        'tax_amount' => 199,
        'fee_amount' => 99,
    ]);

    $invoice->refresh();

    expect($invoice->amount)->toBe(1999)
        ->and($invoice->tax_amount)->toBe(199)
        ->and($invoice->fee_amount)->toBe(99)
        ->and(is_int($invoice->amount))->toBeTrue()
        ->and(is_int($invoice->tax_amount))->toBeTrue()
        ->and(is_int($invoice->fee_amount))->toBeTrue();
});

it('allows nullable tax and fee amounts', function (): void {
    $invoice = Invoice::factory()->create([
        'tax_amount' => null,
        'fee_amount' => null,
    ]);

    $invoice->refresh();

    expect($invoice->tax_amount)->toBeNull()
        ->and($invoice->fee_amount)->toBeNull();
});

it('enforces unique constraint on organizer_id, year, and number', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => 2026,
        'number' => 1,
    ]);

    expect(fn () => Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => 2026,
        'number' => 1,
    ]))->toThrow(QueryException::class);
});

it('allows same number for different organizers in same year', function (): void {
    Invoice::factory()->create(['year' => 2026, 'number' => 1]);
    Invoice::factory()->create(['year' => 2026, 'number' => 1]);

    expect(Invoice::query()->where('year', 2026)->count())->toBe(2);
});

it('allows same number for same organizer in different years', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => 2025,
        'number' => 1,
    ]);
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => 2026,
        'number' => 1,
    ]);

    expect(Invoice::query()->where('organizer_id', $organizer->id)->count())->toBe(2);
});

it('belongs to an organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->for($organizer, 'organizer')->create();

    expect($invoice->organizer)->toBeInstanceOf(Organizer::class)
        ->and($invoice->organizer->id)->toBe($organizer->id);
});

it('belongs to a ticket order', function (): void {
    $order = TicketOrder::factory()->create();
    $invoice = Invoice::factory()->create(['ticket_order_id' => $order->ticket_order_id]);

    expect($invoice->ticketOrder)->toBeInstanceOf(TicketOrder::class)
        ->and($invoice->ticketOrder->ticket_order_id)->toBe($order->ticket_order_id);
});

it('belongs to a payment', function (): void {
    $payment = Payment::factory()->create();
    $invoice = Invoice::factory()->create(['payment_id' => $payment->payment_id]);

    expect($invoice->payment)->toBeInstanceOf(Payment::class)
        ->and($invoice->payment->payment_id)->toBe($payment->payment_id);
});

it('belongs to a refund (credit note)', function (): void {
    $payment = Payment::factory()->create();
    $refund = Refund::factory()->create(['payment_id' => $payment->payment_id]);
    $invoice = Invoice::factory()->creditNote()->create([
        'refund_id' => $refund->refund_id,
    ]);

    expect($invoice->refund)->toBeInstanceOf(Refund::class)
        ->and($invoice->refund->refund_id)->toBe($refund->refund_id);
});

it('requires a ticket order and organizer', function (): void {
    expect(fn () => Invoice::factory()->create(['organizer_id' => null]))
        ->toThrow(QueryException::class);

    expect(fn () => Invoice::factory()->create(['ticket_order_id' => null]))
        ->toThrow(QueryException::class);
});

it('generates correct invoice number format', function (): void {
    $invoice = Invoice::factory()->create([
        'type' => InvoiceType::Invoice,
        'year' => 2026,
        'number' => 42,
    ]);

    expect($invoice->invoice_number)->toBe('INV-2026-0042');
});

it('generates correct credit note number format', function (): void {
    $invoice = Invoice::factory()->creditNote()->create([
        'year' => 2026,
        'number' => 7,
    ]);

    expect($invoice->invoice_number)->toMatch('/^CN-\d{4}-\d{4}$/');
});

it('can be soft deleted', function (): void {
    $invoice = Invoice::factory()->create();
    $invoiceId = $invoice->invoice_id;

    $invoice->delete();

    expect(Invoice::query()->find($invoiceId))->toBeNull()
        ->and(Invoice::withTrashed()->find($invoiceId))->not->toBeNull();
});
