<?php

declare(strict_types=1);

use App\Actions\Auth\RecordAuthActivityAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('records an auth activity for successful registration', function (): void {
    $user = User::factory()->create();

    (resolve(RecordAuthActivityAction::class))(
        event: 'register',
        subject: $user,
        causerId: $user->id,
        context: ['outcome' => 'success'],
    );

    $activity = Activity::query()->where('event', 'register')->sole();

    expect($activity->log_name)->toBe('auth')
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(User::class)
        ->and($activity->causer_id)->toBe($user->id);
});

it('records separate auth activities for login and logout', function (): void {
    $user = User::factory()->create();
    $action = resolve(RecordAuthActivityAction::class);

    $action(event: 'login', subject: $user, causerId: $user->id, context: ['outcome' => 'success']);
    $action(event: 'logout', subject: $user, causerId: $user->id, context: ['outcome' => 'success']);

    expect(Activity::query()->where('event', 'login')->count())->toBe(1)
        ->and(Activity::query()->where('event', 'logout')->count())->toBe(1);
});

it('records password reset request and completion without storing secrets or tokens', function (): void {
    $user = User::factory()->create();
    $action = resolve(RecordAuthActivityAction::class);

    $action(
        event: 'password-reset-request',
        subject: $user,
        context: ['outcome' => 'sent', 'token' => 'SUPER-SECRET-TOKEN', 'password' => 'plain-secret'],
    );

    $action(
        event: 'password-reset-completed',
        subject: $user,
        causerId: $user->id,
        context: ['outcome' => 'reset', 'token' => 'SUPER-SECRET-TOKEN'],
    );

    $requestActivity = Activity::query()->where('event', 'password-reset-request')->sole();
    $completedActivity = Activity::query()->where('event', 'password-reset-completed')->sole();

    expect($requestActivity->properties->toArray())->toBe(['outcome' => 'sent'])
        ->and($completedActivity->properties->toArray())->toBe(['outcome' => 'reset']);
});

it('never persists sensitive keys in stored audit properties', function (): void {
    $user = User::factory()->create();

    (resolve(RecordAuthActivityAction::class))(
        event: 'login',
        subject: $user,
        context: [
            'password' => 'plain-secret',
            'password_confirmation' => 'plain-secret',
            'token' => 'reset-token-value',
            'secret' => 'leak',
            'api_token' => 'api-leak',
            'authorization' => 'Bearer leak',
            'outcome' => 'success',
            'ip' => '127.0.0.1',
        ],
    );

    $properties = Activity::query()->where('event', 'login')->sole()->properties->toArray();

    expect($properties)
        ->not->toHaveKey('password')
        ->and($properties)->not->toHaveKey('password_confirmation')
        ->and($properties)->not->toHaveKey('token')
        ->and($properties)->not->toHaveKey('secret')
        ->and($properties)->not->toHaveKey('api_token')
        ->and($properties)->not->toHaveKey('authorization')
        ->and($properties)->toHaveKey('outcome')
        ->and($properties)->toHaveKey('ip');
});

it('keeps the auth flow response safe when activity logging fails, exposing no internal details or secrets', function (): void {
    // Create the subject before binding the failing logger so the User model's
    // own LogsActivity trait does not trip the failure boundary under test.
    $user = User::factory()->create();

    // Force the Activitylog persistence action to throw, simulating a logging failure.
    $this->app->bind(LogActivityAction::class, fn () => new class extends LogActivityAction
    {
        public function execute(Model $activity, string $description): Model
        {
            throw new RuntimeException('DB connection lost during audit insert; payload contained token=SUPER-SECRET');
        }
    });

    // The audit Action MUST swallow the logging failure and return normally.
    $ranToCompletion = false;

    try {
        (resolve(RecordAuthActivityAction::class))(
            event: 'login',
            subject: $user,
            context: ['password' => 'plain-secret', 'token' => 'SUPER-SECRET', 'outcome' => 'success'],
        );
        $ranToCompletion = true;
    } catch (Throwable) {
        // If an exception escapes, the failure boundary is broken.
    }

    expect($ranToCompletion)->toBeTrue()
        ->and(Activity::query()->where('event', 'login')->count())->toBe(0);
});
