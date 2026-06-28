<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

function attachOrganizerRole(Organizer $organizer, User $user, OrganizerRoles $role): void
{
    $organizer->users()->attach($user->id, ['role' => $role->value]);
}

it('renders organizer table search results', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Organizer::factory()->create(['name' => 'North Festival', 'slug' => 'north-festival']);
    Organizer::factory()->create(['name' => 'South Summit', 'slug' => 'south-summit']);

    $this->actingAs($user);

    Livewire::test('organizers.organizers-table')
        ->set('search', 'North')
        ->assertSee('North Festival')
        ->assertDontSee('South Summit');
});

it('forbids organizer deletion from users without delete permission', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    $this->actingAs($user);

    Livewire::test('organizers.organizers-table')
        ->set('organizerIdToDelete', $organizer->id)
        ->call('deleteOrganizer')
        ->assertForbidden();

    $this->assertDatabaseHas('organizers', [
        'id' => $organizer->id,
        'deleted_at' => null,
    ]);
});

it('forbids team actions from organizer viewers', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    attachOrganizerRole($organizer, $viewer, OrganizerRoles::Viewer);

    $this->actingAs($viewer);

    Livewire::test('organizers.team-table', ['organizer' => $organizer])
        ->call('openAddModal')
        ->assertForbidden();
});

it('does not delete events outside the mounted organizer scope', function (): void {
    $organizer = Organizer::factory()->create();
    $otherOrganizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    attachOrganizerRole($organizer, $admin, OrganizerRoles::Admin);

    $otherEvent = Event::factory()->create(['organizer_id' => $otherOrganizer->id]);

    $this->actingAs($admin);

    Livewire::test('organizers.events-table', ['organizer' => $organizer])
        ->set('eventIdToDelete', $otherEvent->getKey())
        ->call('deleteEvent')
        ->assertNotFound();

    $this->assertDatabaseHas('event', [
        'event_id' => $otherEvent->getKey(),
        'deleted_at' => null,
    ]);
});

it('does not delete venues outside the mounted organizer scope', function (): void {
    $organizer = Organizer::factory()->create();
    $otherOrganizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    attachOrganizerRole($organizer, $admin, OrganizerRoles::Admin);

    $otherVenue = Venue::factory()->create(['organizer_id' => $otherOrganizer->id]);

    $this->actingAs($admin);

    Livewire::test('organizers.venues-table', ['organizer' => $organizer])
        ->set('venueIdToDelete', $otherVenue->getKey())
        ->call('deleteVenue')
        ->assertNotFound();

    $this->assertDatabaseHas('venue', [
        'venue_id' => $otherVenue->getKey(),
        'deleted_at' => null,
    ]);
});
