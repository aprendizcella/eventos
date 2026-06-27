<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    // Set team context for global roles (using 0 as sentinel for "no specific team")
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

// =============================================================================
// Form Component Rendering Tests
// =============================================================================

it('renders form input component with label and error state', function (): void {
    $html = Blade::render('<x-form.input name="email" label="Email" :value="\'test@example.com\'" required help="Enter your email" />');

    expect($html)
        ->toContain('name="email"')
        ->toContain('id="email"')
        ->toContain('value="test@example.com"')
        ->toContain('Email')
        ->toContain('required')
        ->toContain('Enter your email');
});

it('renders form select component with associative options', function (): void {
    $html = Blade::render(
        '<x-form.select name="status" label="Status" :options="[\'active\' => \'Active\', \'inactive\' => \'Inactive\']" :selected="\'active\'" />',
    );

    expect($html)
        ->toContain('name="status"')
        ->toContain('value="active"')
        ->toContain('selected')
        ->toContain('Active')
        ->toContain('value="inactive"')
        ->toContain('Inactive');
});

it('renders form select with placeholder', function (): void {
    $html = Blade::render(
        '<x-form.select name="role" label="Role" :options="[\'admin\' => \'Admin\', \'editor\' => \'Editor\']" placeholder="Select a role..." />',
    );

    expect($html)
        ->toContain('<option value="">Select a role...</option>')
        ->toContain('name="role"');
});

it('renders form password component with Alpine toggle', function (): void {
    $html = Blade::render('<x-form.password name="password" label="Password" required />');

    expect($html)
        ->toContain('x-data="{ shown: false }"')
        ->toContain(':type="shown ? \'text\' : \'password\'"')
        ->toContain('@click="shown = !shown"')
        ->toContain('name="password"');
});

it('renders form date component', function (): void {
    $html = Blade::render('<x-form.date name="start_date" label="Start Date" :value="\'2024-01-15\'" />');

    expect($html)
        ->toContain('type="date"')
        ->toContain('name="start_date"')
        ->toContain('value="2024-01-15"');
});

it('renders form time component', function (): void {
    $html = Blade::render('<x-form.time name="start_time" label="Start Time" :value="\'14:30\'" />');

    expect($html)
        ->toContain('type="time"')
        ->toContain('name="start_time"')
        ->toContain('value="14:30"');
});

it('renders form textarea component', function (): void {
    $html = Blade::render('<x-form.textarea name="description" label="Description" :value="\'Some text\'" rows="6" />');

    expect($html)
        ->toContain('<textarea')
        ->toContain('name="description"')
        ->toContain('rows="6"')
        ->toContain('Some text');
});

it('renders form checkbox component', function (): void {
    $html = Blade::render('<x-form.checkbox name="agree" label="I agree" value="1" />');

    expect($html)
        ->toContain('type="checkbox"')
        ->toContain('name="agree"')
        ->toContain('value="1"')
        ->toContain('I agree');
});

it('renders form radio component', function (): void {
    $html = Blade::render('<x-form.radio name="color" label="Red" value="red" />');

    expect($html)
        ->toContain('type="radio"')
        ->toContain('name="color"')
        ->toContain('value="red"')
        ->toContain('Red');
});

it('renders form toggle component with Alpine', function (): void {
    $html = Blade::render('<x-form.toggle name="notifications" label="Enable notifications" />');

    expect($html)
        ->toContain('on: false')
        ->toContain("fieldName: 'notifications'")
        ->toContain("fieldValue: '1'")
        ->toContain('role="switch"')
        ->toContain('@click="on = !on"')
        ->toContain('Enable notifications');
});

it('renders form file component', function (): void {
    $html = Blade::render('<x-form.file name="avatar" label="Avatar" accept="image/*" />');

    expect($html)
        ->toContain('type="file"')
        ->toContain('name="avatar"')
        ->toContain('accept="image/*"');
});

it('renders form dropzone component with drag state', function (): void {
    $html = Blade::render('<x-form.dropzone name="document" label="Upload Document" hint="PDF, DOC up to 5MB" />');

    expect($html)
        ->toContain('x-data')
        ->toContain('dragging')
        ->toContain('type="file"')
        ->toContain('name="document"')
        ->toContain('PDF, DOC up to 5MB');
});

it('renders form input-group with prefix and suffix', function (): void {
    $html = Blade::render(
        '<x-form.input-group label="Price" name="price"><x-slot:prefix>$</x-slot:prefix><input type="number" name="price" class="w-full border px-3 py-2"><x-slot:suffix>.00</x-slot:suffix></x-form.input-group>',
    );

    expect($html)
        ->toContain('$')
        ->toContain('.00')
        ->toContain('name="price"');
});

// =============================================================================
// Organizer View Integration Tests
// =============================================================================

it('renders organizer create page with reusable select component for status', function (): void {
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('organizers.create'));

    $response->assertOk()
        ->assertSee('name="status"', false)
        ->assertSee('<option value="active"', false)
        ->assertSee('<option value="inactive"', false)
        ->assertSee('Status');
});

it('renders organizer edit page with reusable select component for status', function (): void {
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::query()->create(['name' => 'Test Org', 'slug' => 'test-org', 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('organizers.edit', $organizer));

    $response->assertOk()
        ->assertSee('name="status"', false)
        ->assertSee('<option value="active" selected', false)
        ->assertSee('<option value="inactive"', false);
});

it('renders organizer team index with reusable select components in modals', function (): void {
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::query()->create(['name' => 'Test Org', 'slug' => 'test-org']);

    // Assign user as admin of the organizer so they can manage team
    $organizer->users()->attach($user->id, ['role' => App\Support\Organizers\OrganizerRoles::Admin->value]);

    $response = $this->actingAs($user)->get(route('organizers.team.index', $organizer));

    $response->assertOk()
        ->assertSee('name="user_id"', false)
        ->assertSee('name="role"', false)
        ->assertSee('Select a user', false);
});

it('renders status select with correct selected value from old input', function (): void {
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    // Simulate a validation error with old input
    $response = $this->actingAs($user)->post(route('organizers.store'), [
        'name' => '',
        'slug' => '',
        'status' => 'inactive',
    ]);

    $response->assertSessionHasErrors(['name', 'slug']);

    // Now render the create page with old input
    $response = $this->actingAs($user)->get(route('organizers.create'));

    $response->assertOk()
        ->assertSee('<option value="inactive" selected', false);
});
