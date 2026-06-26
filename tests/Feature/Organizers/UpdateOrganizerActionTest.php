<?php

declare(strict_types=1);

use App\Actions\Organizers\UpdateOrganizerAction;
use App\DataTransferObjects\Organizers\UpdateOrganizerDto;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('updates an organizer with valid data', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create([
        'name' => 'Old Name',
        'slug' => 'old-slug',
        'domain' => 'old.example.com',
        'settings' => ['theme' => 'light'],
        'status' => 'active',
    ]);

    $dto = new UpdateOrganizerDto(
        name: 'New Name',
        slug: 'new-slug',
        domain: 'new.example.com',
        settings: ['theme' => 'dark'],
        status: 'inactive',
    );

    $action = resolve(UpdateOrganizerAction::class);
    $updated = $action($organizer, $dto, $user);

    expect($updated->name)->toBe('New Name')
        ->and($updated->slug)->toBe('new-slug')
        ->and($updated->domain)->toBe('new.example.com')
        ->and($updated->settings)->toBe(['theme' => 'dark'])
        ->and($updated->status)->toBe('inactive');
});

it('logs activity when updating organizer', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create([
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $dto = new UpdateOrganizerDto(
        name: 'Updated',
        slug: 'updated',
    );

    $action = resolve(UpdateOrganizerAction::class);
    $action($organizer, $dto, $user);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('organizer');
});

it('updates only provided fields', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create([
        'name' => 'Original',
        'slug' => 'original',
        'domain' => 'original.com',
        'status' => 'active',
    ]);

    $dto = new UpdateOrganizerDto(
        name: 'Updated',
        slug: 'original',
    );

    $action = resolve(UpdateOrganizerAction::class);
    $updated = $action($organizer, $dto, $user);

    expect($updated->name)->toBe('Updated')
        ->and($updated->slug)->toBe('original')
        ->and($updated->domain)->toBe('original.com')
        ->and($updated->status)->toBe('active');
});
