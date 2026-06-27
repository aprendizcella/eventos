<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('updates user name with valid input', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put(route('account.profile.update'), [
            'name' => 'New Name',
        ])
        ->assertRedirect(route('account.profile.edit'))
        ->assertSessionHas('status');

    expect($user->fresh()->name)->toBe('New Name');
});

it('rejects empty name on profile update', function (): void {
    $user = User::factory()->create(['name' => 'Valid Name']);

    $this->actingAs($user)
        ->put(route('account.profile.update'), [
            'name' => '',
        ])
        ->assertSessionHasErrors(['name']);

    expect($user->fresh()->name)->toBe('Valid Name');
});

it('does not modify email even if submitted', function (): void {
    $user = User::factory()->create([
        'name' => 'Original',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user)
        ->put(route('account.profile.update'), [
            'name' => 'Updated',
            'email' => 'hacked@example.com',
        ])
        ->assertRedirect(route('account.profile.edit'));

    expect($user->fresh()->email)->toBe('original@example.com')
        ->and($user->fresh()->name)->toBe('Updated');
});

it('displays current name and email on profile page', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertSee('Test User')
        ->assertSee('test@example.com');
});

it('includes a visible link to change password from the profile page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertSee('Change password')
        ->assertSee('href="'.route('account.password.edit').'"', false);
});

it('renders email field as disabled or readonly', function (): void {
    $user = User::factory()->create(['email' => 'readonly@example.com']);

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertSee('disabled', false);
});
