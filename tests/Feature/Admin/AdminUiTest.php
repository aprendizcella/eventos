<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Setup super admin role
    setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'organizer_id' => 0]);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'organizer_id' => 0]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->platformAdmin = User::factory()->create();
    $this->platformAdmin->assignRole('platform_admin');

    $this->user = User::factory()->create();
});

it('prevents non-admins from seeing admin dashboard', function () {
    $this->actingAs($this->user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows super_admin to see admin dashboard', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire('admin.dashboard');
});

it('allows platform_admin to see admin dashboard', function () {
    $this->actingAs($this->platformAdmin)
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire('admin.dashboard');
});

it('prevents non-admins from seeing users list', function () {
    $this->actingAs($this->user)
        ->get('/admin/users')
        ->assertForbidden();
});

it('allows admins to see users list', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSeeLivewire('admin.users');
});

it('prevents non-admins from seeing events list', function () {
    $this->actingAs($this->user)
        ->get('/admin/events')
        ->assertForbidden();
});

it('allows admins to see events list', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/events')
        ->assertOk()
        ->assertSeeLivewire('admin.events');
});

it('prevents non-admins from seeing platform settings', function () {
    $this->actingAs($this->user)
        ->get('/admin/settings')
        ->assertForbidden();
});

it('allows admins to see platform settings', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertSeeLivewire('admin.settings');
});
