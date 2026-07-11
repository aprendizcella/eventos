<?php

declare(strict_types=1);

use App\DataTransferObjects\Reports\ReportFilterDto;
use App\Models\Organizer;
use App\Services\Reports\ReportAggregationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// --------------------------------------------------------------------------
// Helpers — we use DB::table directly to avoid factory circular deps
// --------------------------------------------------------------------------

function createOrganizer(): Organizer
{
    return Organizer::factory()->create();
}

/**
 * Create a minimal invoice record with the full FK chain.
 */
function insertInvoice(int $organizerId, array $overrides = []): int
{
    // Create event first (needed for ticket_order FK)
    $eventId = DB::table('event')->insertGetId([
        'organizer_id' => $organizerId,
        'title' => 'Test Event',
        'slug' => 'test-event-'.fake()->unique()->randomNumber(6),
        'status' => 'draft',
        'visibility' => 'private',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create ticket_order (needed for invoice FK)
    $ticketOrderId = DB::table('ticket_order')->insertGetId([
        'event_id' => $eventId,
        'order_reference' => 'ORD-'.strtoupper(fake()->bothify('????????')),
        'status' => 'completed',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'subtotal' => 100.00,
        'discount' => 0.00,
        'total' => 100.00,
        'reserved_until' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $maxNumber = (int) DB::table('invoice')->max('number');

    $data = array_merge([
        'organizer_id' => $organizerId,
        'ticket_order_id' => $ticketOrderId,
        'payment_id' => null,
        'refund_id' => null,
        'type' => 'invoice',
        'year' => (int) now()->format('Y'),
        'number' => $maxNumber + 1,
        'invoice_number' => 'INV-'.now()->format('Y').'-'.str_pad((string) ($maxNumber + 1), 4, '0', STR_PAD_LEFT),
        'amount' => 1000,
        'tax_amount' => 100,
        'fee_amount' => 50,
        'currency' => 'USD',
        'status' => 'issued',
        'notes' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides);

    // Ensure FK columns don't break
    unset($data['event_id']);

    return DB::table('invoice')->insertGetId($data);
}

/**
 * Create a minimal payout record linked to an invoice.
 */
function insertPayout(int $organizerId, int $invoiceId, array $overrides = []): int
{
    $data = array_merge([
        'organizer_id' => $organizerId,
        'invoice_id' => $invoiceId,
        'refund_id' => null,
        'gross_amount' => 10000,
        'commission_amount' => 1200,
        'net_amount' => 8800,
        'currency' => 'USD',
        'status' => 'pending',
        'processed_at' => null,
        'reversed_at' => null,
        'notes' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides);

    return DB::table('payout')->insertGetId($data);
}

// --------------------------------------------------------------------------
// 1. Default filter applies last 90 days correctly
// --------------------------------------------------------------------------

it('defaults to last 90 days when no explicit filter is given', function (): void {
    $default = ReportFilterDto::default();

    expect($default->dateFrom)->toBeInstanceOf(Carbon::class)
        ->and($default->dateTo)->toBeInstanceOf(Carbon::class)
        ->and($default->dateFrom->diffInDays($default->dateTo))->toBeLessThanOrEqual(91)
        ->and($default->currency)->toBeNull()
        ->and($default->organizerId)->toBeNull()
        ->and($default->eventId)->toBeNull();
});

it('only aggregates records within the default 90-day window', function (): void {
    $organizer = createOrganizer();

    // Invoice from 200 days ago (outside default window)
    insertInvoice($organizer->id, [
        'amount' => 10000,
        'created_at' => now()->subDays(200),
        'updated_at' => now()->subDays(200),
    ]);

    // Invoice from 50 days ago (inside default window)
    insertInvoice($organizer->id, [
        'amount' => 5000,
        'created_at' => now()->subDays(50),
        'updated_at' => now()->subDays(50),
    ]);

    $service = new ReportAggregationService;
    $filter = ReportFilterDto::default();

    $results = $service->aggregate($filter);

    expect($results)->toBeCollection()
        ->and($results->count())->toBe(1);

    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(5000)
        ->and($usd->invoiceCount)->toBe(1);
});

// --------------------------------------------------------------------------
// 2. Aggregation totals match expected values
// --------------------------------------------------------------------------

it('calculates invoice aggregates correctly', function (): void {
    $organizer = createOrganizer();

    foreach (range(1, 3) as $i) {
        insertInvoice($organizer->id, [
            'amount' => 2000,
            'tax_amount' => 200,
            'fee_amount' => 100,
            'currency' => 'USD',
        ]);
    }

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);

    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(6000)
        ->and($usd->totalTax)->toBe(600)
        ->and($usd->totalFees)->toBe(300)
        ->and($usd->invoiceCount)->toBe(3);
});

it('calculates payout aggregates correctly', function (): void {
    $organizer = createOrganizer();

    // Each payout needs a distinct invoice (payout.invoice_id is unique)
    $invoiceIds = [];

    foreach (range(1, 3) as $i) {
        $invoiceIds[] = insertInvoice($organizer->id, ['amount' => 5000]);
    }

    foreach ($invoiceIds as $invoiceId) {
        insertPayout($organizer->id, $invoiceId, [
            'gross_amount' => 2000,
            'commission_amount' => 200,
            'net_amount' => 1800,
            'currency' => 'USD',
        ]);
    }

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);

    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalGross)->toBe(6000)
        ->and($usd->totalCommission)->toBe(600)
        ->and($usd->totalNet)->toBe(5400)
        ->and($usd->payoutCount)->toBe(3);
});

it('groups aggregates by currency', function (): void {
    $organizer = createOrganizer();

    insertInvoice($organizer->id, ['amount' => 1000, 'currency' => 'USD']);
    insertInvoice($organizer->id, ['amount' => 2000, 'currency' => 'EUR']);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(2)
        ->and($results->firstWhere('currency', 'USD')->totalRevenue)->toBe(1000)
        ->and($results->firstWhere('currency', 'EUR')->totalRevenue)->toBe(2000);
});

// --------------------------------------------------------------------------
// 3. Organizer scope isolation
// --------------------------------------------------------------------------

it('only returns data for the specified organizer', function (): void {
    $organizerA = createOrganizer();
    $organizerB = createOrganizer();

    insertInvoice($organizerA->id, ['amount' => 9999, 'currency' => 'USD']);
    insertInvoice($organizerB->id, ['amount' => 1111, 'currency' => 'USD']);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto(organizerId: $organizerA->id);

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);
    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(9999)
        ->and($usd->totalGross)->toBe(0);
});

it('filters by organizer for both invoices and payouts', function (): void {
    $organizerA = createOrganizer();
    $organizerB = createOrganizer();

    $invoiceAId = insertInvoice($organizerA->id, ['amount' => 5000]);
    $invoiceBId = insertInvoice($organizerB->id, ['amount' => 3000]);

    insertPayout($organizerA->id, $invoiceAId, [
        'gross_amount' => 5000,
        'net_amount' => 4400,
    ]);
    insertPayout($organizerB->id, $invoiceBId, [
        'gross_amount' => 3000,
        'net_amount' => 2600,
    ]);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto(organizerId: $organizerA->id);

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);
    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(5000)
        ->and($usd->totalGross)->toBe(5000)
        ->and($usd->totalNet)->toBe(4400);
});

// --------------------------------------------------------------------------
// 4. Empty state when no matching data
// --------------------------------------------------------------------------

it('returns empty collection when no data exists', function (): void {
    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toBeCollection()->and($results)->toBeEmpty();
});

it('returns empty collection when filter excludes all data', function (): void {
    $organizer = createOrganizer();

    insertInvoice($organizer->id, ['amount' => 5000, 'currency' => 'USD']);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto(currency: 'XYZ');

    $results = $service->aggregate($filter);

    expect($results)->toBeCollection()->and($results)->toBeEmpty();
});

it('returns empty collection for an organizer with no data', function (): void {
    $organizerWithData = createOrganizer();
    $organizerWithoutData = createOrganizer();

    insertInvoice($organizerWithData->id, ['amount' => 5000, 'currency' => 'USD']);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto(organizerId: $organizerWithoutData->id);

    $results = $service->aggregate($filter);

    expect($results)->toBeCollection()->and($results)->toBeEmpty();
});

// --------------------------------------------------------------------------
// 5. Edge cases: single row, multiple rows
// --------------------------------------------------------------------------

it('handles single row correctly', function (): void {
    $organizer = createOrganizer();

    insertInvoice($organizer->id, [
        'amount' => 7777,
        'tax_amount' => 700,
        'fee_amount' => 350,
        'currency' => 'USD',
    ]);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);

    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(7777)
        ->and($usd->totalTax)->toBe(700)
        ->and($usd->totalFees)->toBe(350)
        ->and($usd->invoiceCount)->toBe(1);
});

it('handles multiple rows with different currencies', function (): void {
    $organizer = createOrganizer();

    insertInvoice($organizer->id, ['amount' => 1000, 'currency' => 'USD']);
    insertInvoice($organizer->id, ['amount' => 2000, 'currency' => 'EUR']);
    insertInvoice($organizer->id, ['amount' => 3000, 'currency' => 'ARS']);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(3);
});

it('includes null tax and fee as zero in aggregation', function (): void {
    $organizer = createOrganizer();

    insertInvoice($organizer->id, [
        'amount' => 5000,
        'tax_amount' => null,
        'fee_amount' => null,
        'currency' => 'USD',
    ]);

    $service = new ReportAggregationService;
    $filter = new ReportFilterDto;

    $results = $service->aggregate($filter);

    expect($results)->toHaveCount(1);

    $usd = $results->firstWhere('currency', 'USD');
    expect($usd)->not->toBeNull()
        ->and($usd->totalRevenue)->toBe(5000)
        ->and($usd->totalTax)->toBe(0)
        ->and($usd->totalFees)->toBe(0);
});
