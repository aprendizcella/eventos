<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'organizer_id' => 0]);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'organizer_id' => 0]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->platformAdmin = User::factory()->create();
    $this->platformAdmin->assignRole('platform_admin');

    $this->user = User::factory()->create();
});

it('requires authentication for admin API', function () {
    $this->getJson('/api/v1/admin/users')
        ->assertUnauthorized();
});

it('prevents non-admins from using admin API', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/admin/users')
        ->assertForbidden();
});

it('applies rate limiting to admin API', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($this->superAdmin)->getJson('/api/v1/admin/users');
    }

    $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/admin/users')
        ->assertStatus(429);
});

it('paginates users list', function () {
    User::factory()->count(20)->create();

    $response = $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/admin/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    expect(count($response->json('data')))->toBeLessThanOrEqual(15);
});

it('allows platform_admin to fetch users', function () {
    $this->actingAs($this->platformAdmin)
        ->getJson('/api/v1/admin/users')
        ->assertOk();
});

it('fetches a specific user', function () {
    $target = clone $this->user;

    $this->actingAs($this->superAdmin)
        ->getJson("/api/v1/admin/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'is_suspended']]);
});

it('returns 404 when fetching a non-existent user', function () {
    $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/admin/users/999999')
        ->assertNotFound();
});

it('requires authentication to fetch a specific user', function () {
    $this->getJson("/api/v1/admin/users/{$this->user->id}")
        ->assertUnauthorized();
});

it('prevents non-admins from fetching a specific user', function () {
    $this->actingAs($this->user)
        ->getJson("/api/v1/admin/users/{$this->superAdmin->id}")
        ->assertForbidden();
});

it('defers GDPR deletion by not exposing any delete endpoint', function () {
    // Assert that a DELETE request to a user endpoint returns 405 Method Not Allowed
    // which proves the endpoint does not exist. This fulfills the SDD requirement
    // that GDPR user deletion remains out of scope for Sprint 6.1.
    $this->actingAs($this->superAdmin)
        ->deleteJson("/api/v1/admin/users/{$this->user->id}")
        ->assertStatus(405);
});

it('prevents platform_admin from suspending user', function () {
    $userToSuspend = User::factory()->create();

    $this->actingAs($this->platformAdmin)
        ->postJson("/api/v1/admin/users/{$userToSuspend->id}/suspend")
        ->assertForbidden();
});

it('allows super_admin to suspend user', function () {
    $userToSuspend = User::factory()->create();

    $this->actingAs($this->superAdmin)
        ->postJson("/api/v1/admin/users/{$userToSuspend->id}/suspend")
        ->assertOk();

    expect($userToSuspend->fresh()->suspended_at)->not->toBeNull();
});

it('allows super_admin to fetch events', function () {
    Event::factory()->count(3)->create();

    $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/admin/events')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('allows platform_admin to suspend event', function () {
    $event = Event::factory()->create();

    $this->actingAs($this->platformAdmin)
        ->postJson("/api/v1/admin/events/{$event->event_id}/suspend", [
            'reason' => 'Violation of terms',
        ])
        ->assertOk();

    expect($event->fresh()->status)->toBe(App\Enums\EventStatus::Suspended);
});

it('allows super_admin to suspend event', function () {
    $event = Event::factory()->create();

    $this->actingAs($this->superAdmin)
        ->postJson("/api/v1/admin/events/{$event->event_id}/suspend", [
            'reason' => 'Violation of terms',
        ])
        ->assertOk();

    expect($event->fresh()->status)->toBe(App\Enums\EventStatus::Suspended);
});
