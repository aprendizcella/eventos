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

it('throttles repeated login POST attempts after the configured limit and stays validation-safe', function (): void {
    User::factory()->create([
        'email' => 'throttle-login@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $payload = [
        'email' => 'throttle-login@example.com',
        'password' => 'wrong-password',
    ];

    // The named `throttle:login` limiter allows 5 attempts per minute per email|ip.
    // The first 5 attempts MUST reach the controller and return validation-safe
    // feedback (generic auth.failed message, no credential leak).
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $this->post('/login', $payload);

        $response->assertSessionHasErrors(['email']);

        $errors = session('errors')?->get('email') ?? '';
        expect($errors)
            ->toContain(trans('auth.failed'))
            ->and($errors)->not->toContain('Sup3rSecret!')
            ->and($errors)->not->toContain('wrong-password');
    }

    $this->assertGuest();

    // The 6th attempt within the same minute MUST be rate-limited (429).
    $throttled = $this->post('/login', $payload);

    $throttled->assertTooManyRequests();

    // The throttle response MUST NOT leak the submitted credentials, the email
    // under attack, or any validation message that would distinguish existing
    // vs non-existing accounts.
    $body = $throttled->getContent() ?? '';
    expect($body)
        ->not->toContain('throttle-login@example.com')
        ->not->toContain('wrong-password')
        ->not->toContain('Sup3rSecret!')
        ->not->toContain(trans('auth.failed'));
});

it('throttles repeated password reset request POST attempts after the configured limit and stays validation-safe', function (): void {
    // Use a non-existing email on purpose: the throttle boundary MUST hold
    // regardless of whether the account exists, and the 429 response MUST NOT
    // reveal which case it is.
    $payload = ['email' => 'throttle-reset@example.com'];

    // The named `throttle:password-reset-request` limiter allows 5 attempts per
    // minute per email|ip. The first 5 attempts MUST reach the controller and
    // return a redirect back to the forgot-password form (no raw reset token,
    // no exception, no secret leak). The failure path keeps the user on the
    // form so the error surfaces there.
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $this->post('/forgot-password', $payload);

        $response->assertRedirect(route('forgot-password'));

        $body = $response->getContent() ?? '';
        expect($body)
            ->not->toContain('throttle-reset@example.com')
            ->and($body)->not->toMatch('/token|bearer|secret|api[_-]?key/i');
    }

    // The 6th attempt within the same minute MUST be rate-limited (429).
    $throttled = $this->post('/forgot-password', $payload);

    $throttled->assertTooManyRequests();

    $body = $throttled->getContent() ?? '';
    expect($body)
        ->not->toContain('throttle-reset@example.com')
        ->and($body)->not->toMatch('/token|bearer|secret|api[_-]?key/i');
});
