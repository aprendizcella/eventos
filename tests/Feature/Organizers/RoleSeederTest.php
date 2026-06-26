<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('seeds only three global roles', function (): void {
    $seeder = new RoleSeeder();
    $seeder->run();

    $roles = Role::all()->pluck('name')->toArray();

    expect($roles)->toHaveCount(3)
        ->and($roles)->toContain('super_admin')
        ->and($roles)->toContain('platform_admin')
        ->and($roles)->toContain('attendee');
});

it('does not seed organizer_admin as global role', function (): void {
    $seeder = new RoleSeeder();
    $seeder->run();

    $role = Role::where('name', 'organizer_admin')->first();

    expect($role)->toBeNull();
});

it('does not seed organizer_editor as global role', function (): void {
    $seeder = new RoleSeeder();
    $seeder->run();

    $role = Role::where('name', 'organizer_editor')->first();

    expect($role)->toBeNull();
});

it('does not seed organizer_viewer as global role', function (): void {
    $seeder = new RoleSeeder();
    $seeder->run();

    $role = Role::where('name', 'organizer_viewer')->first();

    expect($role)->toBeNull();
});

it('seeder is idempotent', function (): void {
    $seeder = new RoleSeeder();
    
    $seeder->run();
    $firstRun = Role::count();
    
    $seeder->run();
    $secondRun = Role::count();

    expect($firstRun)->toBe($secondRun)->and($firstRun)->toBe(3);
});
