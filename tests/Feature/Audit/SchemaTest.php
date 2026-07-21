<?php

declare(strict_types=1);

use App\Models\Organizer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('valid classifications are accepted', function () {
    // legacy
    $legacy = Activity::query()->create([
        'description' => 'legacy',
        'is_global' => false,
        'organizer_id' => null,
    ]);
    expect($legacy->id)->not->toBeNull();

    // global
    $global = Activity::query()->create([
        'description' => 'global',
        'is_global' => true,
        'organizer_id' => null,
    ]);
    expect($global->id)->not->toBeNull();

    // owned
    $organizer = Organizer::factory()->create();
    $owned = Activity::query()->create([
        'description' => 'owned',
        'is_global' => false,
        'organizer_id' => $organizer->id,
    ]);
    expect($owned->id)->not->toBeNull();
});

test('invariant rejection prevents global with organizer', function () {
    $organizer = Organizer::factory()->create();

    // Insert
    expect(fn () => Activity::query()->create([
        'description' => 'invalid',
        'is_global' => true,
        'organizer_id' => $organizer->id,
    ]))->toThrow(QueryException::class);

    // Update
    $activity = Activity::query()->create([
        'description' => 'valid-legacy',
        'is_global' => false,
        'organizer_id' => null,
    ]);

    expect(fn () => $activity->update([
        'is_global' => true,
        'organizer_id' => $organizer->id,
    ]))->toThrow(QueryException::class);
});

test('restrictOnDelete blocks physical deletion but allows soft deletion', function () {
    $organizer = Organizer::factory()->create();

    $owned = Activity::query()->create([
        'description' => 'owned',
        'is_global' => false,
        'organizer_id' => $organizer->id,
    ]);

    // Soft delete should work
    $organizer->delete();
    expect($organizer->trashed())->toBeTrue();
    expect($owned->fresh()->organizer_id)->toBe($organizer->id);

    // Physical delete should fail due to RESTRICT foreign key
    expect(fn () => $organizer->forceDelete())->toThrow(QueryException::class);
    expect($owned->fresh()->organizer_id)->toBe($organizer->id);
});

test('migration integrity and rollback', function () {
    // Check columns and indexes
    expect(Schema::hasColumns('activity_log', ['organizer_id', 'is_global']))->toBeTrue();

    $indexes = collect(Schema::getIndexes('activity_log'));

    // Check index order
    $organizerIndex = $indexes->firstWhere('name', 'activity_log_organizer_id_created_at_index');
    expect($organizerIndex)->not->toBeNull()
        ->and($organizerIndex['columns'])->toBe(['organizer_id', 'created_at']);

    $globalIndex = $indexes->firstWhere('name', 'activity_log_is_global_created_at_index');
    expect($globalIndex)->not->toBeNull()
        ->and($globalIndex['columns'])->toBe(['is_global', 'created_at']);

    // Rollback
    $this->artisan('migrate:rollback', [
        '--path' => 'database/migrations/2026_07_20_175300_add_ownership_to_activity_log_table.php',
    ])->assertSuccessful();

    expect(Schema::hasColumns('activity_log', ['organizer_id', 'is_global']))->toBeFalse();

    // Migrate back up so we don't break other tests
    $this->artisan('migrate', [
        '--path' => 'database/migrations/2026_07_20_175300_add_ownership_to_activity_log_table.php',
    ])->assertSuccessful();
});

test('migrates a populated activity_log preserving legacy values and distinct states', function () {
    // Rollback to simulate pre-migration state
    $this->artisan('migrate:rollback', [
        '--path' => 'database/migrations/2026_07_20_175300_add_ownership_to_activity_log_table.php',
    ])->assertSuccessful();

    // Insert raw row to bypass model and assume no schema additions
    Illuminate\Support\Facades\DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'pre-migration-legacy',
        'event' => 'created',
        'subject_type' => App\Models\User::class,
        'subject_id' => 1,
        'causer_type' => null,
        'causer_id' => null,
        'properties' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $legacyId = Illuminate\Support\Facades\DB::getPdo()->lastInsertId();

    // Migrate up
    $this->artisan('migrate', [
        '--path' => 'database/migrations/2026_07_20_175300_add_ownership_to_activity_log_table.php',
    ])->assertSuccessful();

    // Verify preservation and defaults
    $migrated = Illuminate\Support\Facades\DB::table('activity_log')->find($legacyId);
    expect($migrated->description)->toBe('pre-migration-legacy')
        ->and((bool) $migrated->is_global)->toBeFalse()
        ->and($migrated->organizer_id)->toBeNull();

    // Prove the unclassified legacy row can be updated independently of global/organizer
    Illuminate\Support\Facades\DB::table('activity_log')
        ->where('id', $legacyId)
        ->update(['description' => 'updated-legacy']);

    $updated = Illuminate\Support\Facades\DB::table('activity_log')->find($legacyId);
    expect($updated->description)->toBe('updated-legacy')
        ->and((bool) $updated->is_global)->toBeFalse()
        ->and($updated->organizer_id)->toBeNull();
});

test('mariadb 11 runtime constraint path', function () {
    if (Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
        test()->markTestSkipped('Configured environment uses sqlite; MariaDB/MySQL native constraints not tested here.');
    }

    // In MariaDB, the invariant is a real check constraint, test it throws
    $organizer = Organizer::factory()->create();

    expect(fn () => Activity::query()->create([
        'description' => 'invalid-mariadb',
        'is_global' => true,
        'organizer_id' => $organizer->id,
    ]))->toThrow(QueryException::class);
});
