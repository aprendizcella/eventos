<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payout;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use App\ViewModels\Organizers\PayoutReportsViewModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// ─── PayoutReportsViewModel ────────────────────────────────────────────────

it('returns empty summaries when no payouts exist', function (): void {
    $organizer = Organizer::factory()->create();

    $viewModel = new PayoutReportsViewModel($organizer);

    expect($viewModel->totalGross())->toBeEmpty()
        ->and($viewModel->totalCommission())->toBeEmpty()
        ->and($viewModel->totalNet())->toBeEmpty()
        ->and($viewModel->csvRows())->toBe([]);
});

it('aggregates gross amounts from payouts', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->count(3)->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ])->each(function (Invoice $invoice) use ($organizer): void {
        Payout::factory()->create([
            'organizer_id' => $organizer->id,
            'invoice_id' => $invoice->invoice_id,
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'net_amount' => 9500,
            'currency' => 'USD',
            'status' => PayoutStatus::Ready,
        ]);
    });

    $viewModel = new PayoutReportsViewModel($organizer);
    $gross = $viewModel->totalGross();

    expect($gross)->toHaveCount(1);
    expect($gross->first()->total_gross)->toBe(30000)
        ->and($gross->first()->payout_count)->toBe(3);
});

it('aggregates commission amounts from payouts', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
    ]);

    $viewModel = new PayoutReportsViewModel($organizer);
    $commission = $viewModel->totalCommission();

    expect($commission)->toHaveCount(1);
    expect($commission->first()->total_commission)->toBe(500);
});

it('aggregates net amounts from payouts', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
    ]);

    $viewModel = new PayoutReportsViewModel($organizer);
    $net = $viewModel->totalNet();

    expect($net)->toHaveCount(1);
    expect($net->first()->total_net)->toBe(9500);
});

it('generates csv rows from recent payouts', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
        'status' => PayoutStatus::Ready,
    ]);

    $viewModel = new PayoutReportsViewModel($organizer);
    $rows = $viewModel->csvRows();

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toHaveKeys(['date', 'invoice_number', 'event', 'gross_amount', 'commission_amount', 'net_amount', 'currency', 'status']);
    expect($rows[0]['gross_amount'])->toBe(10000)
        ->and($rows[0]['commission_amount'])->toBe(500)
        ->and($rows[0]['net_amount'])->toBe(9500)
        ->and($rows[0]['currency'])->toBe('USD')
        ->and($rows[0]['status'])->toBe('ready');
});

it('excludes soft-deleted payouts from summaries', function (): void {
    $organizer = Organizer::factory()->create();

    $activeInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    $deletedInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $activeInvoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
    ]);

    $deleted = Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $deletedInvoice->invoice_id,
        'gross_amount' => 50000,
        'commission_amount' => 2500,
        'net_amount' => 47500,
        'currency' => 'USD',
    ]);
    $deleted->delete(); // soft delete

    $viewModel = new PayoutReportsViewModel($organizer);
    $gross = $viewModel->totalGross();

    expect($gross->first()->total_gross)->toBe(10000)
        ->and($gross->first()->payout_count)->toBe(1);
});

it('denies payout report access to organizer viewers', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Viewer->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertForbidden();
});

it('denies payout report access to organizer editors', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Editor->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertForbidden();
});

it('exports complete CSV without row limit', function (): void {
    $organizer = Organizer::factory()->create();

    // Create 60 payouts (exceeds the former 50-row limit)
    $invoices = Invoice::factory()->count(60)->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    $invoices->each(function (Invoice $invoice) use ($organizer): void {
        Payout::factory()->create([
            'organizer_id' => $organizer->id,
            'invoice_id' => $invoice->invoice_id,
            'gross_amount' => 1000,
            'currency' => 'USD',
        ]);
    });

    $viewModel = new PayoutReportsViewModel($organizer);
    $rows = $viewModel->csvRows();

    expect($rows)->toHaveCount(60);
});

it('filters payouts by date range', function (): void {
    $organizer = Organizer::factory()->create();

    $oldInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    $newInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $oldInvoice->invoice_id,
        'gross_amount' => 5000,
        'created_at' => now()->subDays(60),
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $newInvoice->invoice_id,
        'gross_amount' => 10000,
        'created_at' => now()->subDays(5),
    ]);

    $viewModel = new PayoutReportsViewModel($organizer, [
        'date_from' => now()->subDays(30)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]);

    $gross = $viewModel->totalGross();
    expect($gross->first()->total_gross)->toBe(10000)
        ->and($gross->first()->payout_count)->toBe(1);
});

it('filters payouts by status', function (): void {
    $organizer = Organizer::factory()->create();

    $pendingInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    $processedInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $pendingInvoice->invoice_id,
        'gross_amount' => 5000,
        'status' => PayoutStatus::Pending,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $processedInvoice->invoice_id,
        'gross_amount' => 10000,
        'status' => PayoutStatus::Processed,
    ]);

    // Filter by Processed status
    $viewModel = new PayoutReportsViewModel($organizer, [
        'status' => PayoutStatus::Processed->value,
    ]);

    $gross = $viewModel->totalGross();
    expect($gross->first()->total_gross)->toBe(10000)
        ->and($gross->first()->payout_count)->toBe(1);
});

it('groups summaries by currency', function (): void {
    $organizer = Organizer::factory()->create();

    $usdInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'currency' => 'USD',
    ]);

    $eurInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'currency' => 'EUR',
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $usdInvoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $eurInvoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'EUR',
    ]);

    $viewModel = new PayoutReportsViewModel($organizer);
    expect($viewModel->totalGross())->toHaveCount(2)
        ->and($viewModel->totalCommission())->toHaveCount(2)
        ->and($viewModel->totalNet())->toHaveCount(2);
});

// ─── Livewire Volt Component ───────────────────────────────────────────────

it('renders the payout reports page for authorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    $response = $this->get(route('organizers.reports.payouts', $organizer));
    $response->assertOk();
    $response->assertSee('Payout Reports');
});

it('denies payout reports page to unauthorized users without role', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // No role attached = no view permission

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertForbidden();
});

it('denies payout reports page to unauthorized users not attached', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // Not attached to this organizer = no view permission

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertForbidden();
});

it('shows payout data and summary cards via the Volt component', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 15000,
        'commission_amount' => 750,
        'net_amount' => 14250,
        'currency' => 'USD',
        'status' => PayoutStatus::Ready,
    ]);

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertSee('Payout Reports')
        ->assertSee('Total Gross')
        ->assertSee('Total Commission')
        ->assertSee('Total Net')
        ->assertSee('Recent Payouts')
        ->assertSee('Ready');
});

it('shows warning banner on payout reports page', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->assertSee('internal operational view');
});

it('exports CSV from the payout reports component', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 10000,
        'commission_amount' => 500,
        'net_amount' => 9500,
        'currency' => 'USD',
        'status' => PayoutStatus::Ready,
    ]);

    $this->actingAs($user);

    $response = Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->call('exportCsv');

    $response->assertFileDownloaded();
});

it('filters payouts and updates data via the Volt component', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $pendingInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    $processedInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $pendingInvoice->invoice_id,
        'gross_amount' => 5000,
        'status' => PayoutStatus::Pending,
        'created_at' => now()->subDays(60),
    ]);

    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $processedInvoice->invoice_id,
        'gross_amount' => 15000,
        'status' => PayoutStatus::Processed,
        'created_at' => now()->subDays(5),
    ]);

    $this->actingAs($user);

    Volt::test('organizers.reports.payout-reports', ['organizer' => $organizer])
        ->set('status', PayoutStatus::Processed->value)
        ->call('filter')
        ->assertSee('Processed');
});
