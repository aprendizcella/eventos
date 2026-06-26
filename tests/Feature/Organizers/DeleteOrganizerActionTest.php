<?php

declare(strict_types=1);

use App\Actions\Organizers\DeleteOrganizerAction;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('soft deletes an organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create([
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'status' => 'active',
    ]);

    $action = app(DeleteOrganizerAction::class);
    $action($organizer, $user);

    expect(Organizer::query()->where('id', $organizer->id)->exists())->toBeFalse()
        ->and(Organizer::withTrashed()->where('id', $organizer->id)->exists())->toBeTrue();
});

it('logs activity when deleting organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create([
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
    ]);

    $action = app(DeleteOrganizerAction::class);
    $action($organizer, $user);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('event', 'deleted')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('organizer');
});

it('preserves organizer data after soft delete', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create([
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'domain' => 'test.example.com',
        'settings' => ['theme' => 'dark'],
    ]);

    $action = app(DeleteOrganizerAction::class);
    $action($organizer, $user);

    $deletedOrganizer = Organizer::withTrashed()->find($organizer->id);

    expect($deletedOrganizer)->not->toBeNull()
        ->and($deletedOrganizer->name)->toBe('Test Organizer')
        ->and($deletedOrganizer->slug)->toBe('test-organizer')
        ->and($deletedOrganizer->domain)->toBe('test.example.com')
        ->and($deletedOrganizer->settings)->toBe(['theme' => 'dark']);
});
