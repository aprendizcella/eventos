<?php

declare(strict_types=1);

use App\Actions\Organizers\CreateOrganizerAction;
use App\DataTransferObjects\Organizers\CreateOrganizerDto;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an organizer with valid data', function (): void {
    $user = User::factory()->create();

    $dto = new CreateOrganizerDto(
        name: 'Test Organizer',
        slug: 'test-organizer',
        domain: 'test.example.com',
        settings: ['theme' => 'dark'],
        status: 'active',
    );

    $action = resolve(CreateOrganizerAction::class);
    $organizer = $action($dto, $user);

    expect($organizer)->toBeInstanceOf(Organizer::class)
        ->and($organizer->name)->toBe('Test Organizer')
        ->and($organizer->slug)->toBe('test-organizer')
        ->and($organizer->domain)->toBe('test.example.com')
        ->and($organizer->settings)->toBe(['theme' => 'dark'])
        ->and($organizer->status)->toBe('active');
});

it('logs activity when creating organizer', function (): void {
    $user = User::factory()->create();

    $dto = new CreateOrganizerDto(
        name: 'Test Organizer',
        slug: 'test-organizer',
    );

    $action = resolve(CreateOrganizerAction::class);
    $organizer = $action($dto, $user);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->log_name)->toBe('organizer');
});

it('creates organizer with minimal data', function (): void {
    $user = User::factory()->create();

    $dto = new CreateOrganizerDto(
        name: 'Minimal Organizer',
        slug: 'minimal',
    );

    $action = resolve(CreateOrganizerAction::class);
    $organizer = $action($dto, $user);

    expect($organizer->name)->toBe('Minimal Organizer')
        ->and($organizer->slug)->toBe('minimal')
        ->and($organizer->domain)->toBeNull()
        ->and($organizer->settings)->toBeNull()
        ->and($organizer->status)->toBe('active');
});
