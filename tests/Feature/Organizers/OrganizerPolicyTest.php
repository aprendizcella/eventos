<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Policies\OrganizerPolicy;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'platform_admin', 'guard_name' => 'web']);
});

it('allows organizer admin to view organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new OrganizerPolicy;

    expect($policy->view($user, $organizer))->toBeTrue();
});

it('denies non-member from viewing organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->view($user, $organizer))->toBeFalse();
});

it('allows organizer admin to manage team', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeTrue();
});

it('allows super admin to manage team', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeTrue();
});

it('allows platform admin to manage team', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeTrue();
});

it('denies organizer editor from managing team', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Editor->value]);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeFalse();
});

it('allows super admin to create organizers', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $policy = new OrganizerPolicy;

    expect($policy->create($user))->toBeTrue();
});

it('denies organizer admin from creating organizers', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new OrganizerPolicy;

    expect($policy->create($user))->toBeFalse();
});

it('allows platform admin to delete organizers', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->delete($user, $organizer))->toBeTrue();
});

it('allows organizer admin to view reports', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new OrganizerPolicy;

    expect($policy->viewReports($user, $organizer))->toBeTrue();
});

it('allows super admin to view reports', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->viewReports($user, $organizer))->toBeTrue();
});

it('denies organizer editor from viewing reports', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Editor->value]);

    $policy = new OrganizerPolicy;

    expect($policy->viewReports($user, $organizer))->toBeFalse();
});
