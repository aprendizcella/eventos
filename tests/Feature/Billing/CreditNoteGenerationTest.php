<?php

declare(strict_types=1);

use App\Actions\Payments\IssueCreditNoteAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Events\Payments\RefundProcessed;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('issues a credit note for a completed refund', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 100.00,
        'status' => PaymentStatus::Refunded,
    ]);
    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 100.00,
        'status' => 'completed',
    ]);

    $action = resolve(IssueCreditNoteAction::class);
    $creditNote = $action($refund);

    expect($creditNote)->toBeInstanceOf(Invoice::class)
        ->and($creditNote->type)->toBe(InvoiceType::CreditNote)
        ->and($creditNote->status)->toBe(InvoiceStatus::Issued)
        ->and($creditNote->amount)->toBe(10000) // 100.00 * 100 = 10000 cents
        ->and($creditNote->refund_id)->toBe($refund->refund_id)
        ->and($creditNote->payment_id)->toBe($payment->payment_id)
        ->and($creditNote->ticket_order_id)->toBe($payment->ticket_order_id)
        ->and($creditNote->invoice_number)->toMatch('/^CN-\d{4}-\d{4}$/');
});

it('issues credit note for partial refunds', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 200.00,
        'status' => PaymentStatus::PartiallyRefunded,
    ]);
    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 50.00,
        'status' => 'completed',
    ]);

    $action = resolve(IssueCreditNoteAction::class);
    $creditNote = $action($refund);

    expect($creditNote->amount)->toBe(5000); // 50.00 * 100
});

it('issues sequential credit note numbers for the same organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $payment1 = Payment::factory()->create(['status' => PaymentStatus::Refunded]);
    $payment1->ticketOrder->event->update(['organizer_id' => $organizer->id]);
    $refund1 = Refund::factory()->create([
        'payment_id' => $payment1->payment_id,
        'amount' => 100.00,
        'status' => 'completed',
    ]);

    $payment2 = Payment::factory()->create(['status' => PaymentStatus::Refunded]);
    $payment2->ticketOrder->event->update(['organizer_id' => $organizer->id]);
    $refund2 = Refund::factory()->create([
        'payment_id' => $payment2->payment_id,
        'amount' => 50.00,
        'status' => 'completed',
    ]);

    $action = resolve(IssueCreditNoteAction::class);

    $cn1 = $action($refund1);
    $cn2 = $action($refund2);

    expect($cn2->number)->toBe($cn1->number + 1);
});

it('listener creates credit note when RefundProcessed is dispatched', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 100.00,
        'status' => PaymentStatus::Refunded,
    ]);
    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 100.00,
        'status' => 'completed',
    ]);

    event(new RefundProcessed($refund));

    $this->assertDatabaseHas('invoice', [
        'refund_id' => $refund->refund_id,
        'payment_id' => $payment->payment_id,
        'type' => InvoiceType::CreditNote->value,
        'status' => InvoiceStatus::Issued->value,
        'amount' => 10000,
    ]);
});

it('is idempotent — does not create duplicate credit notes for the same refund', function (): void {
    Event::fake();

    $payment = Payment::factory()->create([
        'amount' => 100.00,
        'status' => PaymentStatus::Refunded,
    ]);
    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 100.00,
        'status' => 'completed',
    ]);

    Invoice::factory()->creditNote()->create([
        'refund_id' => $refund->refund_id,
        'payment_id' => $payment->payment_id,
        'ticket_order_id' => $payment->ticket_order_id,
        'amount' => 10000,
    ]);

    event(new RefundProcessed($refund));

    Event::assertDispatched(RefundProcessed::class);

    $creditNotes = Invoice::query()
        ->where('refund_id', $refund->refund_id)
        ->where('type', InvoiceType::CreditNote)
        ->count();

    expect($creditNotes)->toBe(1);
});
