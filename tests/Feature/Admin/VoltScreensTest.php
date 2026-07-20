<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\PlatformSetting;
use App\Models\User;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

test('admin users screen can suspend users', function () {
    $admin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $admin->assignRole($role);
    $this->actingAs($admin);

    $user = User::factory()->create();

    Volt::test('admin.users')
        ->assertSee($user->email)
        ->call('suspend', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->isSuspended())->toBeTrue();
});

test('admin events screen can suspend events', function () {
    $admin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $admin->assignRole($role);
    $this->actingAs($admin);

    $event = Event::factory()->create();

    Volt::test('admin.events')
        ->assertSee($event->title)
        ->call('suspend', $event->event_id)
        ->assertHasNoErrors();

    expect($event->fresh()->status)->toBe(App\Enums\EventStatus::Suspended);
});

test('admin settings screen can update settings', function () {
    $admin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $admin->assignRole($role);
    $this->actingAs($admin);

    $settings = PlatformSetting::current();

    Volt::test('admin.settings')
        ->set('settings.app_name', 'Testing App')
        ->call('save')
        ->assertHasNoErrors();

    expect(PlatformSetting::current()->setting('app_name'))->toBe('Testing App');
});
