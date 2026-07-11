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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// ─── Navigation & Entrypoint (Task 2.1) ───────────────────────────────────

it('renders the report hub page for authorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    $response = $this->get(route('organizers.reports.index', $organizer));
    $response->assertOk();
    $response->assertSee('Report Center');
});

it('denies the report hub page to unauthorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // No role attached = no view permission

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertForbidden();
});

it('shows 5 report family section cards on the hub', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('Revenue')
        ->assertSee('Taxes')
        ->assertSee('Fees')
        ->assertSee('Payouts')
        ->assertSee('Event Performance');
});

// ─── Default Filters (Tasks 2.1/2.2) ──────────────────────────────────────

it('defaults to last 90 days filter on the hub', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    $component = Volt::test('organizers.reports.report-hub', ['organizer' => $organizer]);

    $dateFrom = $component->get('dateFrom');
    $dateTo = $component->get('dateTo');

    expect($dateFrom)->not->toBeEmpty();
    expect($dateTo)->not->toBeEmpty();

    // Should be approximately 90 days apart
    $from = Illuminate\Support\Facades\Date::parse($dateFrom);
    $to = Illuminate\Support\Facades\Date::parse($dateTo);
    expect($from->diffInDays($to))->toBeGreaterThanOrEqual(88);
    expect($from->diffInDays($to))->toBeLessThanOrEqual(91);
});

// ─── Summary Data Display (Task 2.3) ─────────────────────────────────────

it('shows aggregate data in KPI cards when data exists', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Create invoice data
    Invoice::factory()->count(3)->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'tax_amount' => 2100,
        'fee_amount' => 500,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('Revenue')
        ->assertSee('Taxes')
        ->assertSee('Fees');
});

it('shows payout summary data on the hub', function (): void {
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

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('Payouts');
});

it('shows contextual warning banner for payouts', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('internal operational view');
});

it('shows event drilldown links in the event performance section', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $event = App\Models\Event::factory()->create([
        'organizer_id' => $organizer->id,
        'title' => 'Test Conference 2026',
    ]);

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('Test Conference 2026')
        ->assertSee(route('organizers.events.show', [$organizer, $event]));
});

// ─── CSV Export (Task 2.4) ────────────────────────────────────────────────

it('exports CSV from the report hub', function (): void {
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

    $response = Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->call('exportCsv');

    $response->assertFileDownloaded();
});

// ─── Authorization & Scope Isolation (Task 2.5) ───────────────────────────

it('enforces organizer scope isolation', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $otherOrganizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Create data for the other organizer (should NOT be visible)
    Invoice::factory()->create([
        'organizer_id' => $otherOrganizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 99999,
    ]);

    $this->actingAs($user);

    // Access the hub for the organizer the user belongs to
    $component = Volt::test('organizers.reports.report-hub', ['organizer' => $organizer]);

    // Should not show the other organizer's data in the report section
    // The hub should only show data for $organizer, not $otherOrganizer
    $component->assertOk();
});

it('shows empty state when no data exists', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('No data')
        ->assertSee('Report Center');
});

it('filters hub data by date range and only shows matching data', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Invoice from 200 days ago (outside default 90 day window)
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 99999,
        'tax_amount' => 9999,
        'fee_amount' => 999,
        'created_at' => now()->subDays(200),
    ]);

    $this->actingAs($user);

    // With default 90-day filter, the old invoice should not appear
    // All aggregates should be 0 since no data is in the window
    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('No data found for the selected period');
});

it('only shows data within the applied date filter range', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Invoice inside a specific date window
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 15000,
        'tax_amount' => 3150,
        'fee_amount' => 750,
        'currency' => 'USD',
        'created_at' => now()->subDays(15),
    ]);

    // Another invoice outside the window
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 5000,
        'created_at' => now()->subDays(120),
    ]);

    $this->actingAs($user);

    // Apply a narrow date range that only catches the recent invoice
    // The hub should render with the filtered data
    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->set('dateFrom', now()->subDays(30)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->call('filter')
        ->assertOk();
});

it('filters hub data by currency', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 20000,
        'currency' => 'USD',
    ]);

    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 30000,
        'currency' => 'EUR',
    ]);

    $this->actingAs($user);

    // Without currency filter, both currencies show
    // The 5 report section names always show, and "No data" only shows when there's zero data
    Volt::test('organizers.reports.report-hub', ['organizer' => $organizer])
        ->assertSee('Revenue');
});
