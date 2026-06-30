<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('prevents users from accessing another organizer dashboard', function (): void {
    $user = User::factory()->create();
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    // Assign user to A but not B
    $organizerA->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Access B dashboard -> Forbidden
    $response = $this->actingAs($user)->get(route('organizers.dashboard', $organizerB));
    $response->assertForbidden();

    // Access A dashboard -> Ok
    $response = $this->actingAs($user)->get(route('organizers.dashboard', $organizerA));
    $response->assertOk();
});

it('prevents users from accessing another organizer settings', function (): void {
    $user = User::factory()->create();
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $organizerA->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($user)->get(route('organizers.settings', $organizerB));
    $response->assertForbidden();

    $response = $this->actingAs($user)->get(route('organizers.settings', $organizerA));
    $response->assertOk();
});
