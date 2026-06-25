<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Auth\AuthRouteRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    AuthRouteRegistrar::register();
    $this->withoutMiddleware([ValidateCsrfToken::class]);
});

it('sends a reset link notification and stores a reset token', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->post('/forgot-password', [
        'email' => 'reset@example.com',
    ])
        ->assertSessionHas(['status' => Password::RESET_LINK_SENT]);

    Notification::assertSentTo($user, ResetPassword::class);

    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@example.com']);
});

it('completes the password reset, changes the password, and invalidates the token', function (): void {
    $user = User::factory()->create(['email' => 'complete@example.com']);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'complete@example.com',
        'password' => 'NewSecretPassword1!pwqxy',
        'password_confirmation' => 'NewSecretPassword1!pwqxy',
    ])
        ->assertRedirect('/');

    // Token MUST no longer be reusable.
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'complete@example.com']);

    // The new password MUST authenticate the user.
    $this->post('/login', [
        'email' => 'complete@example.com',
        'password' => 'NewSecretPassword1!pwqxy',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects reset completion with an invalid token and keeps the password unchanged', function (): void {
    User::factory()->create([
        'email' => 'badtoken@example.com',
        'password' => 'OriginalSecret1!',
    ]);

    $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'badtoken@example.com',
        'password' => 'AnotherNewPassword1!pwqxy',
        'password_confirmation' => 'AnotherNewPassword1!pwqxy',
    ])
        ->assertSessionHasErrors(['email']);

    // Original password MUST still work because the reset never happened.
    $this->post('/login', [
        'email' => 'badtoken@example.com',
        'password' => 'OriginalSecret1!',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});

it('keeps the user on the forgot-password form when the reset link request fails', function (): void {
    $this->post('/forgot-password', [
        'email' => 'no-such-user@example.com',
    ])
        ->assertRedirect(route('forgot-password'))
        ->assertSessionHasErrors(['email'])
        ->assertSessionHasInput('email', 'no-such-user@example.com');
});

it('keeps the user on the reset-password form when reset completion fails', function (): void {
    User::factory()->create([
        'email' => 'resetfail@example.com',
        'password' => 'OriginalSecret1!',
    ]);

    $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'resetfail@example.com',
        'password' => 'AnotherNewPassword1!pwqxy',
        'password_confirmation' => 'AnotherNewPassword1!pwqxy',
    ])
        ->assertRedirect(route('password.reset', ['token' => 'invalid-token']))
        ->assertSessionHasErrors(['email']);
});
