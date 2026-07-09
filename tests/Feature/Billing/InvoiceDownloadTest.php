<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('downloads invoice PDF for authorized admin user', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('organizers.invoices.download', [$organizer, $invoice]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertHeader('content-disposition', sprintf('attachment; filename=invoice-%s.pdf', $invoice->invoice_number));
});

it('generates PDF with correct content for an invoice', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'amount' => 2999,
        'tax_amount' => 299,
        'fee_amount' => 99,
    ]);

    $response = $this->actingAs($user)
        ->get(route('organizers.invoices.download', [$organizer, $invoice]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('returns 403 when user does not belong to the organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // Do NOT attach user to organizer

    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('organizers.invoices.download', [$organizer, $invoice]));

    $response->assertForbidden();
});

it('returns 404 when invoice belongs to a different organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $otherOrganizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $invoice = Invoice::factory()->create([
        'organizer_id' => $otherOrganizer->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('organizers.invoices.download', [$organizer, $invoice]));

    $response->assertNotFound();
});

it('requires authentication to download invoice', function (): void {
    $organizer = Organizer::factory()->create();
    $invoice = Invoice::factory()->create([
        'organizer_id' => $organizer->id,
    ]);

    $response = $this->get(route('organizers.invoices.download', [$organizer, $invoice]));

    $response->assertRedirect(route('login'));
});
