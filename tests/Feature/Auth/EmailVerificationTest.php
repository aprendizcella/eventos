<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// =============================================================================
// Verification Notice
// =============================================================================

it('renders the verification notice page for an authenticated unverified user', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verify Your Email Address', false);
});

it('redirects an already verified user away from the notice page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

it('redirects guest to login when accessing the verification notice', function (): void {
    $this->get(route('verification.notice'))
        ->assertRedirect(route('login'));
});

// =============================================================================
// Verified-Only Access Gate
// =============================================================================

it('redirects unverified user from dashboard to verification notice', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('redirects unverified user from account profile to verification notice', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertRedirect(route('verification.notice'));
});

it('redirects unverified user from account password to verification notice', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('account.password.edit'))
        ->assertRedirect(route('verification.notice'));
});

it('redirects unverified user from organizers index to verification notice', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('organizers.index'))
        ->assertRedirect(route('verification.notice'));
});

it('allows verified user to access the dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('allows verified user to access account profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk();
});

it('allows the seeded user to access verified-only routes', function (): void {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

// =============================================================================
// Resend Verification Email
// =============================================================================

it('sends a verification notification on resend for an unverified user', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not resend when user is already verified and redirects to dashboard', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

it('throttles repeated verification email resend requests', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->post(route('verification.send'))
            ->assertRedirect();
    }

    $this->post(route('verification.send'))
        ->assertTooManyRequests();
});

// =============================================================================
// Verification Callback
// =============================================================================

it('verifies the user email via a valid signed callback URL', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1((string) $user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid signature on the verification callback', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1((string) $user->email)]))
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects verification when a different user attempts to verify another account', function (): void {
    $target = User::factory()->unverified()->create();
    $attacker = User::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $target->id, 'hash' => sha1((string) $target->email)],
    );

    $this->actingAs($attacker)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($target->fresh()->hasVerifiedEmail())->toBeFalse();
});

// =============================================================================
// Logout Availability
// =============================================================================

it('allows an unverified authenticated user to logout', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
