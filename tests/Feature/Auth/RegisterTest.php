<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Auth\AuthRouteRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    AuthRouteRegistrar::register();
    $this->withoutMiddleware([ValidateCsrfToken::class]);
});

it('registers and authenticates a new guest, leaving the email unverified', function (): void {
    $response = $this->post('/register', [
        'name' => 'Jose Perez',
        'email' => 'jose@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $response->assertRedirect('/');

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => 'jose@example.com',
        'name' => 'Jose Perez',
    ]);

    $registered = User::query()->where('email', 'jose@example.com')->sole();

    expect($registered->hasVerifiedEmail())->toBeFalse()
        ->and($registered->email_verified_at)->toBeNull();
});

it('keeps access available even though the email is unverified (non-blocking readiness)', function (): void {
    $this->post('/register', [
        'name' => 'Unverified User',
        'email' => 'unverified@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $this->assertAuthenticated();

    // Access MUST continue even if email is unverified: no redirect to a verify page.
    $this->get('/')->assertOk();
});

it('rejects registration with a duplicate email and persists no new user', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'name' => 'Another User',
        'email' => 'taken@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();

    expect(User::query()->where('email', 'taken@example.com')->count())->toBe(1);
});
