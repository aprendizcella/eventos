<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

// =============================================================================
// Helpers
// =============================================================================

function makeOrganizerWithAdmin(): array
{
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    return [$organizer, $admin];
}

function makeOrganizerWithViewer(): array
{
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    return [$organizer, $viewer];
}

// =============================================================================
// Index rendering
// =============================================================================

it('renders the event index with event titles', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $eventA = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Concierto Rock',
        'slug' => 'concierto-rock',
    ]);
    $eventB = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Festival Jazz',
        'slug' => 'festival-jazz',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
    $response->assertSee('Concierto Rock');
    $response->assertSee('Festival Jazz');
    $response->assertSee('Create Event');
});

it('renders empty state when no events exist', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
    $response->assertSee('No events found');
});

// =============================================================================
// Create / Edit forms
// =============================================================================

it('renders the create form with expected fields', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $category = Category::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.create', $organizer));

    $response->assertOk();
    $response->assertSee('Create Event', false);
    $response->assertSee('title', false);
    $response->assertSee('slug', false);
    $response->assertSee('description', false);
    $response->assertSee('starts_at', false);
    $response->assertSee('visibility', false);
    $response->assertSee($category->name);
    $response->assertSee($venue->name);
});

it('renders the edit form prefilled with event data', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Existing Event',
        'slug' => 'existing-event',
        'description' => '<p>Desc</p>',
        'visibility' => EventVisibility::Public,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.edit', [$organizer, $event]));

    $response->assertOk();
    $response->assertSee('Existing Event', false);
    $response->assertSee('existing-event', false);
    $response->assertSee('Update Event', false);
});

// =============================================================================
// Show page
// =============================================================================

it('renders the show page with event details and actions', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Detail Event',
        'slug' => 'detail-event',
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Private,
        'description' => '<p>Detail body</p>',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.show', [$organizer, $event]));

    $response->assertOk();
    $response->assertSee('Detail Event');
    $response->assertSee('Draft', false);
    $response->assertSee('Publish', false);
    $response->assertSee('Cancel', false);
});

it('does not render publish/cancel buttons for viewer', function (): void {
    [$organizer, $viewer] = makeOrganizerWithViewer();

    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'status' => EventStatus::Draft,
    ]);

    $response = $this->actingAs($viewer)
        ->get(route('organizers.events.show', [$organizer, $event]));

    $response->assertOk();
    $response->assertDontSee('name="publish"', false);
    $response->assertDontSee('name="cancel"', false);
});

// =============================================================================
// Filters
// =============================================================================

it('filters events by status', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Draft Event',
        'slug' => 'draft-event',
        'status' => EventStatus::Draft,
    ]);
    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Published Event',
        'slug' => 'published-event',
        'status' => EventStatus::Published,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer).'?status=published');

    $response->assertOk();
    $response->assertSee('Published Event');
    $response->assertDontSee('Draft Event');
});

it('filters events by visibility', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Public One',
        'slug' => 'public-one',
        'visibility' => EventVisibility::Public,
    ]);
    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Private One',
        'slug' => 'private-one',
        'visibility' => EventVisibility::Private,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer).'?visibility=public');

    $response->assertOk();
    $response->assertSee('Public One');
    $response->assertDontSee('Private One');
});

it('filters events by search term in title', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Concierto Rock',
        'slug' => 'concierto-rock',
    ]);
    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Festival Jazz',
        'slug' => 'festival-jazz',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer).'?search=rock');

    $response->assertOk();
    $response->assertSee('Concierto Rock');
    $response->assertDontSee('Festival Jazz');
});

it('filters events by date range', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Past Event',
        'slug' => 'past-event',
        'starts_at' => now()->subMonth(),
    ]);
    Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Future Event',
        'slug' => 'future-event',
        'starts_at' => now()->addMonth(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer).'?starts_from='.now()->subDay()->toDateString().'&starts_until='.now()->addMonths(2)->toDateString());

    $response->assertOk();
    $response->assertSee('Future Event');
    $response->assertDontSee('Past Event');
});

// =============================================================================
// Navigation
// =============================================================================

it('sidebar events link points to current organizer events when in organizer context', function (): void {
    [$organizer, $admin] = makeOrganizerWithAdmin();

    $response = $this->actingAs($admin)
        ->get(route('organizers.events.index', $organizer));

    $response->assertOk();
    $response->assertSee(route('organizers.events.index', $organizer), false);
});
