<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// Set global team context for Spatie roles (super_admin, platform_admin are not tenant-scoped)
beforeEach(function (): void {
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(0);
});

// ─── Navigation & Entrypoint (Task 3.1) ───────────────────────────────────

it('renders the platform report hub page for super_admin', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    $response = $this->get(route('admin.reports.index'));
    $response->assertOk();
    $response->assertSee('Platform Report Center');
});

it('renders the platform report hub page for platform_admin', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
    $user->assignRole('platform_admin');

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->assertSee('Platform Report Center');
});

it('denies the platform report hub to authenticated non-admin users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('admin.reports.index'));
    $response->assertForbidden();
});

it('denies the platform report hub to unauthenticated visitors', function (): void {
    $response = $this->get(route('admin.reports.index'));
    $response->assertRedirect(route('login'));
});

// ─── Default Filters (Task 3.1/3.2) ───────────────────────────────────────

it('defaults to last 90 days filter on the platform hub', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    $component = Volt::test('admin.reports.platform-hub');

    $dateFrom = $component->get('dateFrom');
    $dateTo = $component->get('dateTo');

    expect($dateFrom)->not->toBeEmpty();
    expect($dateTo)->not->toBeEmpty();

    $from = Illuminate\Support\Facades\Date::parse($dateFrom);
    $to = Illuminate\Support\Facades\Date::parse($dateTo);
    expect($from->diffInDays($to))->toBeGreaterThanOrEqual(88);
    expect($from->diffInDays($to))->toBeLessThanOrEqual(91);
});

// ─── Cross-Organizer Data Display (Task 3.2/3.3) ─────────────────────────

it('shows KPI cards when data exists across organizers', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

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

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->assertSee('Platform Report Center')
        ->assertSee('Total Revenue')
        ->assertSee('Total Tax')
        ->assertSee('Total Fees');
});

it('shows aggregate payout data in KPI cards', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
    ]);
    Payout::factory()->create([
        'organizer_id' => $organizer->id,
        'invoice_id' => $invoice->invoice_id,
        'gross_amount' => 20000,
        'commission_amount' => 1000,
        'net_amount' => 19000,
        'currency' => 'USD',
        'status' => PayoutStatus::Ready,
    ]);

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->assertSee('Total Gross')
        ->assertSee('Total Commission')
        ->assertSee('Total Net');
});

it('shows per-organizer breakdown in the table', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $organizerA = Organizer::factory()->create(['name' => 'Org Alpha']);
    $organizerB = Organizer::factory()->create(['name' => 'Org Beta']);

    Invoice::factory()->create([
        'organizer_id' => $organizerA->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 50000,
        'currency' => 'USD',
    ]);
    Invoice::factory()->create([
        'organizer_id' => $organizerB->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 75000,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->assertSee('Org Alpha')
        ->assertSee('Org Beta');
});

// ─── Organizer Filter (Task 3.3) ──────────────────────────────────────────

it('filters data by selected organizer', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $organizerA = Organizer::factory()->create(['name' => 'Org Alpha']);
    $organizerB = Organizer::factory()->create(['name' => 'Org Beta']);

    Invoice::factory()->create([
        'organizer_id' => $organizerA->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 50000,
        'currency' => 'USD',
    ]);
    Invoice::factory()->create([
        'organizer_id' => $organizerB->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 75000,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->set('selectedOrganizerId', (string) $organizerA->id)
        ->call('filter')
        ->assertSee('Org Alpha');
});

// ─── Empty State (Task 3.5) ───────────────────────────────────────────────

it('shows empty state when no data exists in the platform hub', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    Volt::test('admin.reports.platform-hub')
        ->assertSee('Platform Report Center')
        ->assertSee('No data');
});

// ─── CSV Export (Task 3.4) ────────────────────────────────────────────────

it('exports CSV from the platform report hub', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

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

    $this->actingAs($user);

    $response = Volt::test('admin.reports.platform-hub')
        ->call('exportCsv');

    $response->assertFileDownloaded();
});

// ─── Sidebar Navigation (Task 3.1) ────────────────────────────────────────

it('shows platform reports link in the sidebar for admins', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('Platform Reports');
});

it('shows per-currency KPI cards when multiple currencies exist', function (): void {
    $user = User::factory()->create();
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    // USD invoice
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 10000,
        'currency' => 'USD',
    ]);

    // EUR invoice
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'type' => InvoiceType::Invoice,
        'status' => InvoiceStatus::Paid,
        'amount' => 20000,
        'currency' => 'EUR',
    ]);

    $this->actingAs($user);

    // Should show both currency labels
    Volt::test('admin.reports.platform-hub')
        ->assertSee('USD')
        ->assertSee('EUR');
});
