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

it('renders the organizer settings view successfully for authorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($user)->get(route('organizers.settings', $organizer));

    $response->assertOk();
    $response->assertSee('Organizer Settings');
});

it('updates basic info, address, social and default settings successfully', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create(['name' => 'Original Org', 'slug' => 'original-org']);
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', 'Updated Org')
        ->set('slug', 'updated-org')
        ->set('address', '123 Test St')
        ->set('city', 'Test City')
        ->set('country', 'Test Country')
        ->set('currency', 'EUR')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $organizer->refresh();

    expect($organizer->name)->toBe('Updated Org')
        ->and($organizer->slug)->toBe('updated-org')
        ->and($organizer->settings['address']['address'])->toBe('123 Test St')
        ->and($organizer->settings['address']['city'])->toBe('Test City')
        ->and($organizer->settings['defaults']['currency'])->toBe('EUR');
});

it('denies settings updates to unauthorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    // Attached as Viewer, which has no update permission
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Viewer->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', 'Hacked Org')
        ->set('slug', 'hacked-org')
        ->call('saveSettings')
        ->assertForbidden();
});

it('clears domain to null when cleared in form settings', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create([
        'name' => 'Org with Domain',
        'slug' => 'org-with-domain',
        'domain' => 'my.domain.com',
    ]);
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', 'Org with Domain')
        ->set('slug', 'org-with-domain')
        ->set('domain', '')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $organizer->refresh();

    expect($organizer->domain)->toBeNull();
});
