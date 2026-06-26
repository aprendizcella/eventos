<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Policies\OrganizerPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'platform_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    Role::create(['name' => 'viewer', 'guard_name' => 'web']);
});

it('allows organizer admin to view organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($user->id, ['role_id' => $adminRole->id]);

    $policy = new OrganizerPolicy;

    expect($policy->view($user, $organizer))->toBeTrue();
});

it('denies non-member from viewing organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);

    $policy = new OrganizerPolicy;

    expect($policy->view($user, $organizer))->toBeFalse();
});

it('allows organizer admin to manage team', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($user->id, ['role_id' => $adminRole->id]);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeTrue();
});

it('denies organizer editor from managing team', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $editorRole = Role::where('name', 'editor')->first();
    $organizer->users()->attach($user->id, ['role_id' => $editorRole->id]);

    $policy = new OrganizerPolicy;

    expect($policy->manageTeam($user, $organizer))->toBeFalse();
});
