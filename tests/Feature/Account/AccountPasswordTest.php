<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('updates password with valid current password and confirmation', function (): void {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertRedirect(route('account.password.edit'))
        ->assertSessionHas('status');

    expect(Hash::check('NewPassword1!', $user->fresh()->password))->toBeTrue();
});

it('rejects password change when current password is wrong', function (): void {
    $user = User::factory()->create(['password' => Hash::make('CorrectPassword1!')]);

    $this->actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'WrongPassword1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertSessionHasErrors(['current_password']);

    expect(Hash::check('CorrectPassword1!', $user->fresh()->password))->toBeTrue();
});

it('rejects password change when confirmation does not match', function (): void {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'OldPassword1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'Mismatched!',
        ])
        ->assertSessionHasErrors(['password']);

    expect(Hash::check('OldPassword1!', $user->fresh()->password))->toBeTrue();
});

it('rejects password change when new password is too short', function (): void {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'OldPassword1!',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors(['password']);

    expect(Hash::check('OldPassword1!', $user->fresh()->password))->toBeTrue();
});

it('rejects password change when fields are empty', function (): void {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrors(['current_password', 'password']);

    expect(Hash::check('OldPassword1!', $user->fresh()->password))->toBeTrue();
});
