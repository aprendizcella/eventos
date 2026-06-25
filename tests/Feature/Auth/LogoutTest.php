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

it('logs out an authenticated user and ends the session', function (): void {
    $this->actingAs(User::factory()->create());

    $this->assertAuthenticated();

    $this->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
