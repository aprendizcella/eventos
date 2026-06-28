<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ValidateCsrfToken::class]);

    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Route Wiring & Authorization
// =============================================================================

it('allows organizer admin to list events', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    Event::factory()->count(3)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
});

it('allows organizer viewer to list events', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    Event::factory()->count(2)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($viewer)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
});

it('allows organizer admin to view event creation form', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.create', $organizer));

    $response->assertOk();
});

it('denies organizer viewer from viewing event creation form', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $response = $this->actingAs($viewer)
        ->get(route('organizers.events.create', $organizer));

    $response->assertForbidden();
});

it('allows organizer admin to store event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.events.store', $organizer), [
            'title' => 'Test Event',
            'slug' => 'test-event',
            'description' => '<p>Description</p>',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('event', [
        'organizer_id' => $organizer->getKey(),
        'title' => 'Test Event',
        'slug' => 'test-event',
    ]);
});

it('denies organizer viewer from storing event', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $response = $this->actingAs($viewer)
        ->post(route('organizers.events.store', $organizer), [
            'title' => 'Test Event',
            'slug' => 'test-event',
        ]);

    $response->assertForbidden();
});

it('allows organizer editor to store event', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $response = $this->actingAs($editor)
        ->post(route('organizers.events.store', $organizer), [
            'title' => 'Editor Event',
            'slug' => 'editor-event',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('event', [
        'organizer_id' => $organizer->getKey(),
        'title' => 'Editor Event',
    ]);
});

it('allows organizer admin to view single event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.show', [$organizer, $event]));

    $response->assertOk();
});

it('allows organizer admin to edit event', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.edit', [$organizer, $event]));

    $response->assertOk();
});

it('allows organizer editor to update event', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Old Title',
        'slug' => 'old-slug',
    ]);

    $response = $this->actingAs($editor)
        ->put(route('organizers.events.update', [$organizer, $event]), [
            'title' => 'New Title',
            'slug' => 'new-slug',
        ]);

    $response->assertRedirect();

    $event->refresh();
    expect($event->title)->toBe('New Title');
});

it('denies organizer viewer from updating event via route', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($viewer)
        ->put(route('organizers.events.update', [$organizer, $event]), [
            'title' => 'Hacked',
            'slug' => 'hacked',
        ]);

    $response->assertForbidden();
});

// =============================================================================
// Lifecycle Routes (publish, pause, cancel)
// =============================================================================

it('allows organizer admin to publish event via route', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Draft,
        'title' => 'Ready Event',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Description</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.events.publish', [$organizer, $event]));

    $response->assertRedirect();

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Published);
});

it('denies organizer viewer from publishing event via route', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Draft,
    ]);

    $response = $this->actingAs($viewer)
        ->post(route('organizers.events.publish', [$organizer, $event]));

    $response->assertForbidden();
});

it('allows organizer editor to pause event via route', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Published,
    ]);

    $response = $this->actingAs($editor)
        ->post(route('organizers.events.pause', [$organizer, $event]));

    $response->assertRedirect();

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Paused);
});

it('allows organizer admin to cancel event via route', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Draft,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.events.cancel', [$organizer, $event]));

    $response->assertRedirect();

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled);
});

// =============================================================================
// Cross-Organizer Denial via HTTP
// =============================================================================

it('denies admin of organizer A from accessing organizer B event list', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    Event::factory()->count(2)->create(['organizer_id' => $organizerB->getKey()]);

    $response = $this->actingAs($adminA)
        ->get(route('organizers.events.index', $organizerB));

    $response->assertForbidden();
});

it('denies admin of organizer A from viewing organizer B single event', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $eventB = Event::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $response = $this->actingAs($adminA)
        ->get(route('organizers.events.show', [$organizerB, $eventB]));

    $response->assertForbidden();
});

it('denies editor of organizer A from updating organizer B event', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $editorA = User::factory()->create();
    $organizerA->users()->attach($editorA->id, ['role' => OrganizerRoles::Editor->value]);

    $eventB = Event::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $response = $this->actingAs($editorA)
        ->put(route('organizers.events.update', [$organizerB, $eventB]), [
            'title' => 'Hacked',
            'slug' => 'hacked',
        ]);

    $response->assertForbidden();
});

it('denies admin of organizer A from publishing organizer B event', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $eventB = Event::factory()->create([
        'organizer_id' => $organizerB->getKey(),
        'status' => EventStatus::Draft,
        'title' => 'Ready',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $response = $this->actingAs($adminA)
        ->post(route('organizers.events.publish', [$organizerB, $eventB]));

    $response->assertForbidden();
});

// =============================================================================
// Global Admin via HTTP
// =============================================================================

it('allows super_admin to access any organizer event list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    Event::factory()->count(2)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($user)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
});

it('allows super_admin to publish any event', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Draft,
        'title' => 'Ready',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('organizers.events.publish', [$organizer, $event]));

    $response->assertRedirect();

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Published);
});

// =============================================================================
// Non-member denial
// =============================================================================

it('denies non-member from accessing event list', function (): void {
    $user = User::factory()->create();

    $organizer = Organizer::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('organizers.events.index', $organizer));

    $response->assertForbidden();
});
