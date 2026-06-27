<?php

declare(strict_types=1);

use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

/**
 * Helper: load the migration instance from its file.
 */
function loadRoleMigration(): object
{
    return require base_path('database/migrations/2026_06_27_000001_change_organizer_user_role_id_to_role_string.php');
}

beforeEach(function (): void {
    // LazilyRefreshDatabase applies all migrations, so the 'role' column exists.
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role'));
});

it('up() throws RuntimeException before schema changes when dirty role_id data exists', function (): void {
    // Rollback to get to the pre-migration state (role_id column exists)
    Artisan::call('migrate:rollback', [
        '--path' => 'database/migrations/2026_06_27_000001_change_organizer_user_role_id_to_role_string.php',
    ]);

    // Create a role that is NOT in the admin/editor/viewer mapping
    // This simulates dirty data - a role_id that exists but is not mappable
    $unmappedRoleId = DB::table('roles')->insertGetId([
        'name' => 'some_other_role', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $organizerId = DB::table('organizers')->insertGetId([
        'name' => 'Test Org', 'slug' => 'test-org', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizer_user')->insert([
        'organizer_id' => $organizerId, 'user_id' => $userId, 'role_id' => $unmappedRoleId,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Attempt to run up() - should throw before any schema changes
    // The preflight check only maps admin/editor/viewer, so 'some_other_role' is unmapped
    $migration = loadRoleMigration();

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unmapped role_id');

    // Verify schema was NOT mutated: role_id still exists, role column NOT added
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role_id'));
    $this->assertFalse(Schema::hasColumn('organizer_user', 'role'));

    // Verify row data is intact
    $row = DB::table('organizer_user')->where('user_id', $userId)->first();
    expect($row)->not->toBeNull()
        ->and((int) $row->role_id)->toBe($unmappedRoleId);
});

it('up() migrates clean data correctly', function (): void {
    Artisan::call('migrate:rollback', [
        '--path' => 'database/migrations/2026_06_27_000001_change_organizer_user_role_id_to_role_string.php',
    ]);

    $adminRoleId = DB::table('roles')->insertGetId([
        'name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $editorRoleId = DB::table('roles')->insertGetId([
        'name' => 'editor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $organizerId = DB::table('organizers')->insertGetId([
        'name' => 'Test Org', 'slug' => 'test-org', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user1Id = DB::table('users')->insertGetId([
        'name' => 'User 1', 'email' => 'user1@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user2Id = DB::table('users')->insertGetId([
        'name' => 'User 2', 'email' => 'user2@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizer_user')->insert([
        ['organizer_id' => $organizerId, 'user_id' => $user1Id, 'role_id' => $adminRoleId, 'created_at' => now(), 'updated_at' => now()],
        ['organizer_id' => $organizerId, 'user_id' => $user2Id, 'role_id' => $editorRoleId, 'created_at' => now(), 'updated_at' => now()],
    ]);

    loadRoleMigration()->up();

    $this->assertFalse(Schema::hasColumn('organizer_user', 'role_id'));
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role'));

    expect(DB::table('organizer_user')->where('user_id', $user1Id)->value('role'))->toBe('admin')
        ->and(DB::table('organizer_user')->where('user_id', $user2Id)->value('role'))->toBe('editor');
});

it('down() rollback restores role_id correctly', function (): void {
    $organizerId = DB::table('organizers')->insertGetId([
        'name' => 'Test Org', 'slug' => 'test-org', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user1Id = DB::table('users')->insertGetId([
        'name' => 'User 1', 'email' => 'user1@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $user2Id = DB::table('users')->insertGetId([
        'name' => 'User 2', 'email' => 'user2@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizer_user')->insert([
        ['organizer_id' => $organizerId, 'user_id' => $user1Id, 'role' => OrganizerRoles::Admin->value, 'created_at' => now(), 'updated_at' => now()],
        ['organizer_id' => $organizerId, 'user_id' => $user2Id, 'role' => OrganizerRoles::Viewer->value, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $adminRoleId = DB::table('roles')->insertGetId([
        'name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $viewerRoleId = DB::table('roles')->insertGetId([
        'name' => 'viewer', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
    ]);

    loadRoleMigration()->down();

    $this->assertFalse(Schema::hasColumn('organizer_user', 'role'));
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role_id'));

    expect((int) DB::table('organizer_user')->where('user_id', $user1Id)->value('role_id'))->toBe($adminRoleId)
        ->and((int) DB::table('organizer_user')->where('user_id', $user2Id)->value('role_id'))->toBe($viewerRoleId);
});

it('down() throws RuntimeException before schema changes when dirty role data exists', function (): void {
    $organizerId = DB::table('organizers')->insertGetId([
        'name' => 'Test Org', 'slug' => 'test-org', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizer_user')->insert([
        'organizer_id' => $organizerId, 'user_id' => $userId,
        'role' => 'unknown_role', 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Snapshot the roles table before attempting rollback
    $rolesBefore = DB::table('roles')->get()->keyBy('id')->all();
    $rolesCountBefore = DB::table('roles')->count();

    $migration = loadRoleMigration();

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'unmapped role');

    // Verify schema was NOT mutated: role column still exists, role_id NOT added
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role'));
    $this->assertFalse(Schema::hasColumn('organizer_user', 'role_id'));

    // Verify row data is intact
    $row = DB::table('organizer_user')->where('user_id', $userId)->first();
    expect($row)->not->toBeNull()
        ->and($row->role)->toBe('unknown_role');

    // Verify roles table was NOT mutated (no legacy roles created/updated)
    $this->assertSame($rolesCountBefore, DB::table('roles')->count());
    $rolesAfter = DB::table('roles')->get()->keyBy('id')->all();
    expect($rolesAfter)->toBe($rolesBefore);
});

it('down() recreates legacy Spatie roles if missing and completes rollback', function (): void {
    // Delete any existing legacy roles to simulate they were never seeded
    DB::table('roles')->whereIn('name', ['admin', 'editor', 'viewer'])->delete();

    $organizerId = DB::table('organizers')->insertGetId([
        'name' => 'Test Org', 'slug' => 'test-org', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'),
        'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('organizer_user')->insert([
        'organizer_id' => $organizerId, 'user_id' => $userId,
        'role' => OrganizerRoles::Editor->value, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Verify legacy roles are missing before rollback
    $this->assertSame(0, DB::table('roles')->whereIn('name', ['admin', 'editor', 'viewer'])->count());

    // Rollback should succeed and recreate the legacy roles
    loadRoleMigration()->down();

    // Verify legacy roles were recreated
    $this->assertSame(3, DB::table('roles')->whereIn('name', ['admin', 'editor', 'viewer'])->count());

    // Verify schema was restored correctly
    $this->assertFalse(Schema::hasColumn('organizer_user', 'role'));
    $this->assertTrue(Schema::hasColumn('organizer_user', 'role_id'));

    // Verify the role_id was correctly backfilled
    $editorRoleId = DB::table('roles')->where('name', 'editor')->value('id');
    expect((int) DB::table('organizer_user')->where('user_id', $userId)->value('role_id'))->toBe((int) $editorRoleId);
});
