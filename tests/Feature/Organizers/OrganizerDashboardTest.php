<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('renders the organizer dashboard view successfully for authorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    // Assign role inside organizer
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($user)->get(route('organizers.dashboard', $organizer));

    $response->assertOk();
    $response->assertSee($organizer->name);
});

it('renders the dashboard Volt component with correct counters', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    // Create some events using factories
    App\Models\Event::factory()->count(3)->create([
        'organizer_id' => $organizer->id,
        'status' => App\Enums\EventStatus::Published,
    ]);

    $this->actingAs($user);

    Volt::test('organizers.dashboard', ['organizer' => $organizer])
        ->assertViewHas('activeEventsCount', 3)
        ->assertViewHas('teamCount', 1)
        ->assertSee('Overview')
        ->assertSee('Recent Orders');
});
