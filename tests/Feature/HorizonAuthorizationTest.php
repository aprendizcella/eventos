<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $registrar = resolve(Spatie\Permission\PermissionRegistrar::class);
    $registrar->setPermissionsTeamId(0);

    Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Horizon Gate Authorization Tests
// =============================================================================

it('allows super_admin to view Horizon', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    expect(Gate::allows('viewHorizon'))->toBeTrue();
});

it('allows platform_admin to view Horizon', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');

    $this->actingAs($user);

    expect(Gate::allows('viewHorizon'))->toBeTrue();
});

it('denies regular users from viewing Horizon', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Gate::allows('viewHorizon'))->toBeFalse();
});

it('denies unauthenticated requests to Horizon', function (): void {
    $this->get('/horizon')->assertForbidden();
});

it('denies authenticated non-admin access to Horizon route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/horizon')
        ->assertForbidden();
});
