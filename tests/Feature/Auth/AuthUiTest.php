<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ValidateCsrfToken::class]);
});

it('exposes named guest and auth routes', function (): void {
    expect(Route::has('login'))->toBeTruthy()
        ->and(Route::has('login.post'))->toBeTruthy()
        ->and(Route::has('register'))->toBeTruthy()
        ->and(Route::has('register.post'))->toBeTruthy()
        ->and(Route::has('logout'))->toBeTruthy()
        ->and(Route::has('forgot-password'))->toBeTruthy()
        ->and(Route::has('forgot-password.post'))->toBeTruthy()
        ->and(Route::has('password.reset'))->toBeTruthy()
        ->and(Route::has('password.reset.post'))->toBeTruthy()
        ->and(Route::has('verification.notice'))->toBeTruthy()
        ->and(Route::has('verification.verify'))->toBeTruthy()
        ->and(Route::has('verification.send'))->toBeTruthy();
});

it('renders the login page with a form posting to the backend', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertSee('Forgot your password?')
        ->assertSee('href="'.route('forgot-password').'"', false)
        ->assertSee('action="'.route('login.post').'"', false);
});

it('renders a remember me checkbox that is unchecked by default on the login page', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Remember me')
        ->assertSee('name="remember"', false)
        ->assertSee('type="checkbox"', false);
});

it('preserves remember me checkbox state after validation failure', function (): void {
    User::factory()->create([
        'email' => 'ui-remember@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'ui-remember@example.com',
        'password' => 'wrong-password',
        'remember' => '1',
    ])->assertSessionHasErrors(['email']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('checked', false);
});

it('renders the registration page with a form posting to the backend', function (): void {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertSee('Confirm password')
        ->assertSee('name="password_confirmation"', false)
        ->assertSee('x-data="{ shown: false }"', false)
        ->assertSee(':type="shown ? \'text\' : \'password\'"', false)
        ->assertSee('action="'.route('register.post').'"', false);
});

it('renders the forgot-password page with a form posting to the backend', function (): void {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Email')
        ->assertSee('action="'.route('forgot-password.post').'"', false);
});

it('renders the reset-password page with a form posting to the backend', function (): void {
    $this->get('/reset-password/test-token')
        ->assertOk()
        ->assertSee('Password')
        ->assertSee('action="'.route('password.reset.post').'"', false);
});

it('renders the verification notice page for an authenticated unverified user', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verify Your Email Address', false)
        ->assertSee('action="'.route('verification.send').'"', false)
        ->assertSee('action="'.route('logout').'"', false);
});

it('submits registration through the real backend route and authenticates', function (): void {
    $this->post('/register', [
        'name' => 'UI User',
        'email' => 'uiuser@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ])->assertRedirect(route('verification.notice'));

    $this->assertAuthenticated();
});

it('rejects duplicate registration through the real backend route with no new user', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', [
        'name' => 'Duplicate User',
        'email' => 'taken@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ])->assertSessionHasErrors(['email']);

    $this->assertGuest();

    expect(User::query()->where('email', 'taken@example.com')->count())->toBe(1);
});

it('submits login through the real backend route and authenticates', function (): void {
    $user = User::factory()->create([
        'email' => 'uilogin@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'uilogin@example.com',
        'password' => 'Sup3rSecret!',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid login credentials through the real backend route with no session', function (): void {
    User::factory()->create([
        'email' => 'validuser@example.com',
        'password' => 'Sup3rSecret!',
    ]);

    $this->post('/login', [
        'email' => 'validuser@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('submits logout through the real backend route and ends the session', function (): void {
    $this->actingAs(User::factory()->create());

    $this->assertAuthenticated();

    $this->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

it('submits forgot-password through the real backend route', function (): void {
    User::factory()->create(['email' => 'uiforgot@example.com']);

    $this->post('/forgot-password', [
        'email' => 'uiforgot@example.com',
    ])->assertSessionHas(['status' => Password::RESET_LINK_SENT]);
});

it('submits reset-password through the real backend route', function (): void {
    $user = User::factory()->create(['email' => 'uireset@example.com']);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'uireset@example.com',
        'password' => 'NewSecretPassword1!xyz',
        'password_confirmation' => 'NewSecretPassword1!xyz',
    ])->assertRedirect('/');

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'uireset@example.com']);
});
