<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $registrar = resolve(Spatie\Permission\PermissionRegistrar::class);
    $registrar->setPermissionsTeamId(0);

    Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Sidebar Horizon Link Visibility Tests
// =============================================================================
// The Gate allows both super_admin and platform_admin to access Horizon,
// but the sidebar link is only rendered for super_admin (following the
// existing Platform Administration pattern).

it('shows the Horizon link for super_admin', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertSee('Queue Monitor');
});

it('hides the Horizon link from platform_admin', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('platform_admin');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertDontSee('Queue Monitor');
});

it('hides the Horizon link from regular users', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertDontSee('Queue Monitor');
});
