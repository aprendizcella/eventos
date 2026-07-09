<?php

declare(strict_types=1);

use App\Actions\Payments\AdjustPayoutAction;
use App\Enums\InvoiceType;
use App\Enums\PayoutStatus;
use App\Events\Payments\RefundProcessed;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('reverses a payout when a full refund is processed', function (): void {
    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'amount' => 10000,
    ]);

    $payout = Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'status' => PayoutStatus::Ready,
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 100.00,
        'status' => 'completed',
    ]);

    $action = resolve(AdjustPayoutAction::class);
    $result = $action($refund);

    expect($result)->not->toBeNull()
        ->and($result->status)->toBe(PayoutStatus::Reversed)
        ->and($result->reversed_at)->not->toBeNull()
        ->and($result->refund_id)->toBe($refund->refund_id)
        ->and($result->gross_amount)->toBe(10000)
        ->and($result->commission_amount)->toBe(500);
});

it('adjusts payout amounts when a partial refund is processed', function (): void {
    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'amount' => 10000,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 1000,
        'net_amount' => 9000,
        'status' => PayoutStatus::Ready,
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 30.00,
        'status' => 'completed',
    ]);

    $action = resolve(AdjustPayoutAction::class);
    $result = $action($refund);

    expect($result)->not->toBeNull()
        ->and($result->status)->toBe(PayoutStatus::Ready)
        ->and($result->gross_amount)->toBe(7000)
        ->and($result->commission_amount)->toBe(700)
        ->and($result->net_amount)->toBe(6300);
});

it('returns null when no invoice exists for the refunded payment', function (): void {
    $payment = Payment::factory()->create();
    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 50.00,
    ]);

    $action = resolve(AdjustPayoutAction::class);
    $result = $action($refund);

    expect($result)->toBeNull();
});

it('returns null when no payout exists for the invoice', function (): void {
    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'amount' => 10000,
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 50.00,
    ]);

    $action = resolve(AdjustPayoutAction::class);
    $result = $action($refund);

    expect($result)->toBeNull();
});

it('listener adjusts payout on RefundProcessed event', function (): void {
    Illuminate\Support\Facades\Event::fake([
        RefundProcessed::class,
    ]);

    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'amount' => 5000,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 5000,
        'commission_amount' => 250,
        'net_amount' => 4750,
        'status' => PayoutStatus::Ready,
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 10.00,
        'status' => 'completed',
    ]);

    $listener = resolve(App\Listeners\Payments\AdjustPayoutOnRefundProcessed::class);
    $listener->handle(new RefundProcessed($refund));

    $this->assertDatabaseHas('payout', [
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 4000,
        'commission_amount' => 200,
    ]);
});

it('listener reverses payout on full refund event', function (): void {
    Illuminate\Support\Facades\Event::fake([
        RefundProcessed::class,
    ]);

    $organizer = Organizer::factory()->create();
    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'amount' => 5000,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 5000,
        'commission_amount' => 250,
        'net_amount' => 4750,
        'status' => PayoutStatus::Ready,
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->payment_id,
        'amount' => 50.00,
        'status' => 'completed',
    ]);

    $listener = resolve(App\Listeners\Payments\AdjustPayoutOnRefundProcessed::class);
    $listener->handle(new RefundProcessed($refund));

    $this->assertDatabaseHas('payout', [
        'invoice_id' => $invoice->invoice_id,
        'status' => PayoutStatus::Reversed->value,
    ]);
});
