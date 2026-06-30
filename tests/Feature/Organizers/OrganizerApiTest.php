<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('returns unauthorized when calling organizer api without token', function (): void {
    $organizer = Organizer::factory()->create();

    $response = $this->getJson(route('api.organizers.show', $organizer));

    $response->assertUnauthorized();
});

it('returns organizer details when calling api with valid credentials', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.organizers.show', $organizer));

    $response->assertOk()
        ->assertJsonPath('data.name', $organizer->name)
        ->assertJsonPath('data.slug', $organizer->slug);
});

it('returns forbidden if user is not part of the organizer when calling api', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.organizers.show', $organizer));

    $response->assertForbidden();
});

it('lists organizer events via api', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $event = App\Models\Event::factory()->create([
        'organizer_id' => $organizer->id,
        'title' => 'API Conference',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.events.index', $organizer));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'API Conference');
});
