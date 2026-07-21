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

it('prevents non-admins from seeing admin reports', function () {
    $this->actingAs($this->user)
        ->get('/admin/reports')
        ->assertForbidden();
});

it('allows super_admin to see admin reports', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/reports')
        ->assertOk()
        ->assertSeeLivewire('admin.reports.platform-hub');
});

it('prevents non-admins from seeing audit logs', function () {
    $this->actingAs($this->user)
        ->get('/admin/audit-logs')
        ->assertForbidden();
});

it('allows super_admin to see audit logs', function () {
    $this->actingAs($this->superAdmin)
        ->get('/admin/audit-logs')
        ->assertOk()
        ->assertSeeLivewire('admin.audit-log');
});
