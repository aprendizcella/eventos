<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Activitylog\Models\Activity;
use Tests\Auth\AuthRouteRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    AuthRouteRegistrar::register();
    $this->withoutMiddleware([ValidateCsrfToken::class]);
});

it('records an auth activity when a guest registers through the flow', function (): void {
    $this->post('/register', [
        'name' => 'Audit Register',
        'email' => 'auditregister@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'auditregister@example.com')->sole();

    $activity = Activity::query()->where('event', 'register')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(User::class)
        ->and($activity->properties->toArray())->toBe(['outcome' => 'success']);
});

it('records a login auth activity when a user signs in through the flow', function (): void {
    $user = User::factory()->create([
        'email' => 'auditlogin@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'auditlogin@example.com',
        'password' => 'Sup3rSecret!',
    ])->assertRedirect('/');

    $activity = Activity::query()->where('event', 'login')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->causer_id)->toBe($user->id);
});

it('records a logout auth activity when a user signs out through the flow', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post('/logout')->assertRedirect('/');

    $activity = Activity::query()->where('event', 'logout')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id);
});

it('records a password-reset-request auth activity without storing the token', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'auditrequest@example.com']);

    $this->post('/forgot-password', [
        'email' => 'auditrequest@example.com',
    ])->assertSessionHas(['status' => Password::RESET_LINK_SENT]);

    $activity = Activity::query()->where('event', 'password-reset-request')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->properties->toArray())->toBe(['outcome' => 'sent'])
        ->and($activity->properties->toArray())->not->toHaveKey('token');
});

it('records a password-reset-completed auth activity without storing secrets', function (): void {
    $user = User::factory()->create(['email' => 'auditcomplete@example.com']);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'auditcomplete@example.com',
        'password' => 'NewSecretPassword1!audit',
        'password_confirmation' => 'NewSecretPassword1!audit',
    ])->assertRedirect('/');

    $activity = Activity::query()->where('event', 'password-reset-completed')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->properties->toArray())->toBe(['outcome' => 'reset'])
        ->and($activity->properties->toArray())->not->toHaveKey('token')
        ->and($activity->properties->toArray())->not->toHaveKey('password');
});
