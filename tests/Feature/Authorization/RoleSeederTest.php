<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

/**
 * Exact set of initial Sprint 1.2 global roles seeded under the web guard,
 * sorted alphabetically so it matches a sorted comparison regardless of
 * insert order. Organizer-scoped roles (admin, editor, viewer) are managed
 * via the organizer_user pivot, not as global Spatie roles.
 */
$expectedRoles = [
    'attendee',
    'platform_admin',
    'super_admin',
];

it('seeds all three global roles exactly once under the web guard', function () use ($expectedRoles): void {
    $this->seed(RoleSeeder::class);

    $seededRoles = Role::query()->where('guard_name', 'web')->pluck('name')->all();
    sort($seededRoles);

    expect($seededRoles)
        ->toHaveCount(3)
        ->and($seededRoles)->toBe($expectedRoles);
});

it('does not duplicate roles when the seeder runs repeatedly (idempotent)', function () use ($expectedRoles): void {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('guard_name', 'web')->count())->toBe(3);

    $seededRoles = Role::query()->where('guard_name', 'web')->pluck('name')->all();
    sort($seededRoles);

    expect($seededRoles)->toBe($expectedRoles);
});

it('leaves existing roles untouched and only fills missing ones on reseed', function (): void {
    Role::query()->firstOrCreate(['name' => 'attendee', 'guard_name' => 'web']);

    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('name', 'attendee')->where('guard_name', 'web')->count())->toBe(1)
        ->and(Role::query()->where('guard_name', 'web')->count())->toBe(3);
});
