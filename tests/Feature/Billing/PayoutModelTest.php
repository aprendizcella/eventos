<?php

declare(strict_types=1);

use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payout;
use App\Models\Refund;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a payout with default values', function (): void {
    $payout = Payout::factory()->create();

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->status)->toBeInstanceOf(PayoutStatus::class)
        ->and($payout->status)->toBe(PayoutStatus::Pending)
        ->and($payout->gross_amount)->toBeInt()
        ->and($payout->commission_amount)->toBeInt()
        ->and($payout->net_amount)->toBeInt()
        ->and($payout->currency)->toBe('USD');
});

it('stores payout amounts as exact integers', function (): void {
    $payout = Payout::factory()->create([
        'gross_amount' => 12345,
        'commission_amount' => 2345,
        'net_amount' => 10000,
    ]);

    $payout->refresh();

    expect($payout->gross_amount)->toBe(12345)
        ->and($payout->commission_amount)->toBe(2345)
        ->and($payout->net_amount)->toBe(10000)
        ->and(is_int($payout->gross_amount))->toBeTrue()
        ->and(is_int($payout->commission_amount))->toBeTrue()
        ->and(is_int($payout->net_amount))->toBeTrue();
});

it('belongs to an organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create(['organizer_id' => $organizer->id]);
    $payout = Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
    ]);

    expect($payout->organizer)->toBeInstanceOf(Organizer::class)
        ->and($payout->organizer->id)->toBe($organizer->id);
});

it('belongs to an invoice', function (): void {
    $invoice = Invoice::factory()->create();
    $payout = Payout::factory()->create(['invoice_id' => $invoice->invoice_id]);

    expect($payout->invoice)->toBeInstanceOf(Invoice::class)
        ->and($payout->invoice->invoice_id)->toBe($invoice->invoice_id);
});

it('belongs to a refund when one exists', function (): void {
    $refund = Refund::factory()->create();
    $invoice = Invoice::factory()->create();
    $payout = Payout::factory()->create([
        'invoice_id' => $invoice->invoice_id,
        'refund_id' => $refund->refund_id,
        'status' => PayoutStatus::Reversed,
        'reversed_at' => now(),
    ]);

    expect($payout->refund)->toBeInstanceOf(Refund::class)
        ->and($payout->refund->refund_id)->toBe($refund->refund_id);
});

it('enforces unique payout per invoice', function (): void {
    $invoice = Invoice::factory()->create();

    Payout::factory()->create(['invoice_id' => $invoice->invoice_id]);

    expect(fn () => Payout::factory()->create(['invoice_id' => $invoice->invoice_id]))
        ->toThrow(QueryException::class);
});

it('invoice has one payout relationship', function (): void {
    $invoice = Invoice::factory()->create();
    $payout = Payout::factory()->create(['invoice_id' => $invoice->invoice_id]);

    expect($invoice->payout)->toBeInstanceOf(Payout::class)
        ->and($invoice->payout->payout_id)->toBe($payout->payout_id);
});

it('can be soft deleted', function (): void {
    $payout = Payout::factory()->create();
    $payoutId = $payout->payout_id;

    $payout->delete();

    expect(Payout::query()->find($payoutId))->toBeNull()
        ->and(Payout::withTrashed()->find($payoutId))->not->toBeNull();
});
