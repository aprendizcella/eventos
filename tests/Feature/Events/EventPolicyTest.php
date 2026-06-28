<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Policies\EventPolicy;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Organizer Role Permissions
// =============================================================================

it('allows organizer admin to view event in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($admin, $event))->toBeTrue();
});

it('allows organizer editor to view event in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($editor, $event))->toBeTrue();
});

it('allows organizer viewer to view event in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($viewer, $event))->toBeTrue();
});

it('allows organizer admin to create event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new EventPolicy;

    expect($policy->create($admin, $organizer))->toBeTrue();
});

it('allows organizer editor to create event', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $policy = new EventPolicy;

    expect($policy->create($editor, $organizer))->toBeTrue();
});

it('denies organizer viewer from creating event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $policy = new EventPolicy;

    expect($policy->create($viewer, $organizer))->toBeFalse();
});

it('allows organizer admin to update event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($admin, $event))->toBeTrue();
});

it('allows organizer editor to update event', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($editor, $event))->toBeTrue();
});

it('denies organizer viewer from updating event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($viewer, $event))->toBeFalse();
});

it('allows organizer admin to publish event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->publish($admin, $event))->toBeTrue();
});

it('allows organizer editor to publish event', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->publish($editor, $event))->toBeTrue();
});

it('denies organizer viewer from publishing event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->publish($viewer, $event))->toBeFalse();
});

it('denies publishing an already published event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Published,
    ]);

    $policy = new EventPolicy;

    expect($policy->publish($admin, $event))->toBeFalse();
});

it('allows organizer admin to pause event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Published,
    ]);

    $policy = new EventPolicy;

    expect($policy->pause($admin, $event))->toBeTrue();
});

it('denies organizer viewer from pausing event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Published,
    ]);

    $policy = new EventPolicy;

    expect($policy->pause($viewer, $event))->toBeFalse();
});

it('denies pausing an event that is not published', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->pause($admin, $event))->toBeFalse();
});

it('allows organizer admin to cancel event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->cancel($admin, $event))->toBeTrue();
});

it('denies organizer viewer from canceling event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->cancel($viewer, $event))->toBeFalse();
});

it('denies canceling a completed event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Completed,
    ]);

    $policy = new EventPolicy;

    expect($policy->cancel($admin, $event))->toBeFalse();
});

// =============================================================================
// Global Admin Access
// =============================================================================

it('allows super_admin to view any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($user, $event))->toBeTrue();
});

it('allows super_admin to create event in any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    $policy = new EventPolicy;

    expect($policy->create($user, $organizer))->toBeTrue();
});

it('allows super_admin to update any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($user, $event))->toBeTrue();
});

it('allows super_admin to publish any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->publish($user, $event))->toBeTrue();
});

it('allows platform_admin to view any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($user, $event))->toBeTrue();
});

it('allows platform_admin to update any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($user, $event))->toBeTrue();
});

// =============================================================================
// Cross-Organizer Isolation
// =============================================================================

it('denies admin of organizer A from viewing organizer B event', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $eventB = Event::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($adminA, $eventB))->toBeFalse();
});

it('denies editor of organizer A from updating organizer B event', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $editorA = User::factory()->create();
    $organizerA->users()->attach($editorA->id, ['role' => OrganizerRoles::Editor->value]);

    $eventB = Event::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $policy = new EventPolicy;

    expect($policy->update($editorA, $eventB))->toBeFalse();
});

it('denies non-member from viewing event', function (): void {
    $user = User::factory()->create();

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new EventPolicy;

    expect($policy->view($user, $event))->toBeFalse();
});

// =============================================================================
// viewAny
// =============================================================================

it('allows organizer member to list events', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $policy = new EventPolicy;

    expect($policy->viewAny($viewer, $organizer))->toBeTrue();
});

it('denies non-member from listing events', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    $policy = new EventPolicy;

    expect($policy->viewAny($user, $organizer))->toBeFalse();
});

it('allows super_admin to list all events', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    $policy = new EventPolicy;

    expect($policy->viewAny($user, $organizer))->toBeTrue();
});
