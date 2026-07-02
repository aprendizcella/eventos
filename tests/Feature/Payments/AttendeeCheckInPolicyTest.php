<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    // Asegurar existencia de roles globales
    Role::query()->firstOrCreate(['name' => 'super_admin']);
    Role::query()->firstOrCreate(['name' => 'platform_admin']);
});

it('allows super admin to perform all check-in actions', function (): void {
    /** @var User $superAdmin */
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $event = Event::factory()->create();

    expect(Gate::forUser($superAdmin)->allows('viewCheckIn', $event))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('checkIn', $event))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('undoCheckIn', $event))->toBeTrue();
});

it('allows organizer admin and editor to check in, but limits viewer to view only', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    // 1. Organizer Admin
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    // 2. Organizer Editor
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    // 3. Organizer Viewer
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    // Verificar Admin
    expect(Gate::forUser($admin)->allows('viewCheckIn', $event))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('checkIn', $event))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('undoCheckIn', $event))->toBeTrue();

    // Verificar Editor
    expect(Gate::forUser($editor)->allows('viewCheckIn', $event))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('checkIn', $event))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('undoCheckIn', $event))->toBeTrue();

    // Verificar Viewer
    expect(Gate::forUser($viewer)->allows('viewCheckIn', $event))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('checkIn', $event))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('undoCheckIn', $event))->toBeFalse();
});

it('denies non-members from performing any action', function (): void {
    $event = Event::factory()->create();
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('viewCheckIn', $event))->toBeFalse()
        ->and(Gate::forUser($stranger)->allows('checkIn', $event))->toBeFalse()
        ->and(Gate::forUser($stranger)->allows('undoCheckIn', $event))->toBeFalse();
});
