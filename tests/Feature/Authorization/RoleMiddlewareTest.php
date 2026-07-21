<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Authorization\AuthorizationRouteRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    AuthorizationRouteRegistrar::register();
    $this->withoutMiddleware([ValidateCsrfToken::class]);

    // Ensure the guarded role exists so assignment is valid regardless of seed state.
    // With Spatie teams enabled, global roles need team_id = 0 explicitly.
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('permits an authenticated user who has the required role', function (): void {
    $user = User::factory()->create();
    // With Spatie teams enabled, we need to set a team context for global roles
    // Using team ID 0 as a sentinel for "no specific team" (global role)
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('role-protected-test')
        ->assertOk()
        ->assertSee('ok');
});

it('denies an authenticated user who lacks the required role', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('role-protected-test')
        ->assertForbidden();
});

it('denies a guest requesting a role-protected route', function (): void {
    $this->get('role-protected-test')->assertForbidden();
});
