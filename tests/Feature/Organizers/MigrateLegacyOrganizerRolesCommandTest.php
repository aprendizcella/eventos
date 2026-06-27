<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    // Set team context for global roles
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

it('reports success when no legacy roles exist', function (): void {
    $this->artisan('organizers:migrate-legacy-roles')
        ->expectsOutput('✓ No legacy organizer_* roles found. Nothing to migrate.')
        ->assertExitCode(0);
});

it('detects legacy organizer_admin role', function (): void {
    Role::query()->firstOrCreate(['name' => 'organizer_admin', 'guard_name' => 'web']);

    $this->artisan('organizers:migrate-legacy-roles --dry-run')
        ->expectsOutputToContain('organizer_admin')
        ->assertExitCode(0);
});

it('detects legacy organizer_editor role', function (): void {
    Role::query()->firstOrCreate(['name' => 'organizer_editor', 'guard_name' => 'web']);

    $this->artisan('organizers:migrate-legacy-roles --dry-run')
        ->expectsOutputToContain('organizer_editor')
        ->assertExitCode(0);
});

it('detects legacy organizer_viewer role', function (): void {
    Role::query()->firstOrCreate(['name' => 'organizer_viewer', 'guard_name' => 'web']);

    $this->artisan('organizers:migrate-legacy-roles --dry-run')
        ->expectsOutputToContain('organizer_viewer')
        ->assertExitCode(0);
});

it('skips users with no organizer membership', function (): void {
    // Use team ID 0 for global roles
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($legacyRole->name);

    $this->artisan('organizers:migrate-legacy-roles --force')
        ->expectsOutputToContain('No organizer membership found')
        ->assertExitCode(0);

    // User should still have legacy role
    expect($user->fresh()->hasRole('organizer_admin'))->toBeTrue();
});

it('migrates user with organizer membership to pivot role', function (): void {
    // Use team ID 0 for global roles
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    // Give user legacy role
    $user->assignRole($legacyRole->name);

    // Give user organizer membership (with any role)
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Viewer->value]);

    $this->artisan('organizers:migrate-legacy-roles --force')
        ->expectsOutputToContain('Migration complete')
        ->assertExitCode(0);

    // User should no longer have legacy role
    expect($user->fresh()->hasRole('organizer_admin'))->toBeFalse();

    // User should have admin role in organizer pivot
    $pivot = DB::table('organizer_user')
        ->where('organizer_id', $organizer->id)
        ->where('user_id', $user->id)
        ->first();

    expect($pivot->role)->toBe(OrganizerRoles::Admin->value);
});

it('deletes legacy roles after migration when no users remain', function (): void {
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_viewer', 'guard_name' => 'web']);

    // No users have this role
    $this->artisan('organizers:migrate-legacy-roles --force')
        ->expectsOutputToContain('Deleted role: organizer_viewer')
        ->assertExitCode(0);

    // Role should be deleted
    expect(Role::query()->where('name', 'organizer_viewer')->exists())->toBeFalse();
});

it('is idempotent when run multiple times', function (): void {
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_editor', 'guard_name' => 'web']);

    // First run
    $this->artisan('organizers:migrate-legacy-roles --force')
        ->assertExitCode(0);

    // Second run should report no legacy roles
    $this->artisan('organizers:migrate-legacy-roles')
        ->expectsOutput('✓ No legacy organizer_* roles found. Nothing to migrate.')
        ->assertExitCode(0);
});

it('clears permission cache after migration', function (): void {
    // Use team ID 0 for global roles
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($legacyRole->name);

    $this->artisan('organizers:migrate-legacy-roles --force')
        ->expectsOutputToContain('Permission cache cleared')
        ->assertExitCode(0);
});

it('supports dry-run mode without making changes', function (): void {
    // Use team ID 0 for global roles
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    $legacyRole = Role::query()->firstOrCreate(['name' => 'organizer_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($legacyRole->name);

    $this->artisan('organizers:migrate-legacy-roles --dry-run')
        ->expectsOutputToContain('DRY RUN')
        ->assertExitCode(0);

    // Legacy role should still exist
    expect(Role::query()->where('name', 'organizer_admin')->exists())->toBeTrue();

    // User should still have legacy role
    expect($user->fresh()->hasRole('organizer_admin'))->toBeTrue();
});
