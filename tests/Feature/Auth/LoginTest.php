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

it('authenticates a user with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'login@example.com',
        'password' => 'Sup3rSecret!',
    ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password with validation-safe feedback and no session', function (): void {
    User::factory()->create([
        'email' => 'valid@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'valid@example.com',
        'password' => 'wrong-password',
    ])
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('rejects credentials for an email that does not exist', function (): void {
    $this->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'Sup3rSecret!',
    ])
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('authenticates without remember me by default', function (): void {
    User::factory()->create([
        'email' => 'noremember@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'noremember@example.com',
        'password' => 'Sup3rSecret!',
    ])
        ->assertRedirect('/');

    $this->assertAuthenticated();
});

it('authenticates with remember me when checkbox is checked', function (): void {
    User::factory()->create([
        'email' => 'remember@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'remember@example.com',
        'password' => 'Sup3rSecret!',
        'remember' => '1',
    ])
        ->assertRedirect('/');

    $this->assertAuthenticated();
});
