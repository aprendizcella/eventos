<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use App\ViewModels\Organizers\BillingReportsViewModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// ─── BillingReportsViewModel ──────────────────────────────────────────────

it('returns empty summaries when no invoices exist', function (): void {
    $organizer = Organizer::factory()->create();

    $viewModel = new BillingReportsViewModel($organizer);

    expect($viewModel->incomeSummary())->toBeEmpty()
        ->and($viewModel->taxSummary())->toBeEmpty()
        ->and($viewModel->feeSummary())->toBeEmpty()
        ->and($viewModel->csvRows())->toBe([]);
});

it('aggregates income summary from paid invoices', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->count(3)->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'tax_amount' => 2100,
        'fee_amount' => 500,
        'currency' => 'USD',
    ]);

    // This should NOT be counted (credit note)
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::CreditNote,
        'status' => InvoiceStatus::Paid,
        'amount' => 5000,
        'currency' => 'USD',
    ]);

    $viewModel = new BillingReportsViewModel($organizer);
    $summary = $viewModel->incomeSummary();

    expect($summary)->toHaveCount(1);
    $row = $summary->first();
    expect($row->currency)->toBe('USD')
        ->and($row->total_income)->toBe(30000)
        ->and($row->total_tax)->toBe(6300)
        ->and($row->total_fees)->toBe(1500)
        ->and($row->invoice_count)->toBe(3);
});

it('aggregates tax summary by currency', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'tax_amount' => 2100,
        'currency' => 'USD',
    ]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 5000,
        'tax_amount' => 1050,
        'currency' => 'EUR',
    ]);

    $viewModel = new BillingReportsViewModel($organizer);
    $taxSummary = $viewModel->taxSummary();

    expect($taxSummary)->toHaveCount(2);
});

it('aggregates fee summary by currency', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'fee_amount' => 500,
        'currency' => 'USD',
    ]);

    $viewModel = new BillingReportsViewModel($organizer);
    $feeSummary = $viewModel->feeSummary();

    expect($feeSummary)->toHaveCount(1);
    expect($feeSummary->first()->total_fees)->toBe(500);
});

it('generates csv rows from income summary', function (): void {
    $organizer = Organizer::factory()->create();

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'tax_amount' => 2100,
        'fee_amount' => 500,
        'currency' => 'USD',
    ]);

    $viewModel = new BillingReportsViewModel($organizer);
    $rows = $viewModel->csvRows();

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toHaveKeys(['currency', 'total_income', 'total_tax', 'total_fees', 'invoice_count']);
});

it('filters invoices by date range', function (): void {
    $organizer = Organizer::factory()->create();

    $oldInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 5000,
        'created_at' => now()->subDays(60),
    ]);

    $newInvoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'created_at' => now()->subDays(5),
    ]);

    $viewModel = new BillingReportsViewModel($organizer, [
        'date_from' => now()->subDays(30)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]);

    $summary = $viewModel->incomeSummary();
    expect($summary->first()->total_income)->toBe(10000)
        ->and($summary->first()->invoice_count)->toBe(1);
});

// ─── Livewire Volt Component ──────────────────────────────────────────────

it('renders the billing reports page for authorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    $response = $this->get(route('organizers.reports.billing', $organizer));
    $response->assertOk();
    $response->assertSee('Billing Reports');
});

it('denies reports page to unauthorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // No role attached = no view permission

    $this->actingAs($user);

    Volt::test('organizers.reports.billing-reports', ['organizer' => $organizer])
        ->assertForbidden();
});

it('filters reports and shows data via the Volt component', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 15000,
        'tax_amount' => 3150,
        'fee_amount' => 750,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    Volt::test('organizers.reports.billing-reports', ['organizer' => $organizer])
        ->assertSee('Income Summary')
        ->assertSee('Tax Summary')
        ->assertSee('Platform Fee Summary')
        ->assertSee('Income Summary');
});

it('exports CSV from the billing reports component', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'tax_amount' => 2100,
        'fee_amount' => 500,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    $response = Volt::test('organizers.reports.billing-reports', ['organizer' => $organizer])
        ->call('exportCsv');

    $response->assertFileDownloaded();
});
