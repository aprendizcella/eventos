<?php

declare(strict_types=1);

use App\Actions\Payments\CreatePayoutAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PayoutStatus;
use App\Events\Payments\PaymentCompleted;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a payout from a paid invoice when fee settings exist', function (): void {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [
                'platform_fee_percentage' => 500,
                'platform_fee_fixed' => 99,
            ],
        ],
    ]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'amount' => 10000,
        'currency' => 'USD',
    ]);

    $action = resolve(CreatePayoutAction::class);
    $payout = $action($invoice);

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->status)->toBe(PayoutStatus::Ready)
        ->and($payout->gross_amount)->toBe(10000)
        ->and($payout->commission_amount)->toBe(599)
        ->and($payout->net_amount)->toBe(9401)
        ->and($payout->currency)->toBe('USD')
        ->and($payout->invoice_id)->toBe($invoice->invoice_id)
        ->and($payout->organizer_id)->toBe($organizer->id);
});

it('does not create a payout when no fee is configured', function (): void {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [],
        ],
    ]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'amount' => 10000,
    ]);

    $action = resolve(CreatePayoutAction::class);
    $payout = $action($invoice);

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->commission_amount)->toBe(550); // 5% + 0.50 of 10000 by default config fallback
});

it('is idempotent — does not create a second payout for the same invoice', function (): void {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [
                'platform_fee_percentage' => 500,
            ],
        ],
    ]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'amount' => 10000,
    ]);

    $action = resolve(CreatePayoutAction::class);

    $first = $action($invoice);
    $second = $action($invoice);

    expect(Payout::query()->count())->toBe(1)
        ->and($second->payout_id)->toBe($first->payout_id);
});

it('skips payout creation when organizer has no fee settings', function (): void {
    $organizer = Organizer::factory()->create(['settings' => null]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'amount' => 10000,
    ]);

    $action = resolve(CreatePayoutAction::class);
    $payout = $action($invoice);

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->commission_amount)->toBe(550); // 5% + 0.50 of 10000 by default config fallback
});

it('listener creates payout on PaymentCompleted when billing is configured', function (): void {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [
                'invoice_enabled' => true,
                'platform_fee_percentage' => 350,
            ],
        ],
    ]);

    $payment = Payment::factory()->create();
    $payment->ticketOrder->event->update(['organizer_id' => $organizer->id]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'ticket_order_id' => $payment->ticketOrder->getKey(),
        'payment_id' => $payment->getKey(),
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 20000,
        'currency' => 'USD',
    ]);

    event(new PaymentCompleted($payment));

    $this->assertDatabaseHas('payout', [
        'invoice_id' => $payment->ticketOrder->invoice->invoice_id,
        'organizer_id' => $organizer->id,
        'gross_amount' => 20000,
    ]);
});

it('listener skips payout when no invoice exists yet', function (): void {
    $payment = Payment::factory()->create();

    event(new PaymentCompleted($payment));

    expect(Payout::query()->count())->toBe(0);
});
