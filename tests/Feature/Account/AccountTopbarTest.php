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
});

it('renders account dropdown with user name for authenticated user on dashboard', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee('Profile')
        ->assertSee('Change password')
        ->assertSee('Sign out');
});

it('displays fallback labels when user has no role or organizer', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No role assigned')
        ->assertSee('No organizer selected');
});

it('displays role label when user has a global role', function (): void {
    Role::findOrCreate('super_admin', 'web');
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Super Admin');
});

it('displays organizer name when user has a current organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Acme Corp', 'slug' => 'acme']);
    $user->organizers()->attach($organizer->id, ['role' => OrganizerRoles::Admin->value]);

    $this->withSession(['current_organizer_id' => $organizer->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Acme Corp');
});

it('does not render account dropdown for guest user', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Sign out');
});

it('includes a logout form posting to the logout route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('action="'.route('logout').'"', false);
});

it('includes a profile link to the account profile route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('account.profile.edit').'"', false);
});

it('includes a change password link in the account dropdown', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('account.password.edit').'"', false);
});
