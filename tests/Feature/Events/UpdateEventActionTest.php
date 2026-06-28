<?php

declare(strict_types=1);

use App\Actions\Events\UpdateEventAction;
use App\DataTransferObjects\Events\UpdateEventDto;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('updates event fields with valid DTO', function (): void {
    $event = Event::factory()->create(['title' => 'Old Title']);

    $dto = new UpdateEventDto(
        title: 'New Title',
        slug: $event->slug,
    );

    $action = resolve(UpdateEventAction::class);
    $updated = $action($event, $dto);

    expect($updated->title)->toBe('New Title');
});

it('updates only provided fields', function (): void {
    $event = Event::factory()->create([
        'title' => 'Original',
        'description' => 'Original desc',
    ]);

    $dto = new UpdateEventDto(
        title: 'Updated',
        slug: $event->slug,
    );

    $action = resolve(UpdateEventAction::class);
    $updated = $action($event, $dto);

    expect($updated->title)->toBe('Updated')
        ->and($updated->description)->toBe('Original desc');
});

it('sanitizes description on update', function (): void {
    $event = Event::factory()->create();

    $dto = new UpdateEventDto(
        title: $event->title,
        slug: $event->slug,
        description: '<p>Safe</p><script>alert(1)</script>',
    );

    $action = resolve(UpdateEventAction::class);
    $updated = $action($event, $dto);

    expect($updated->description)->not->toContain('<script>')
        ->and($updated->description)->toContain('<p>');
});

it('updates visibility', function (): void {
    $event = Event::factory()->create(['visibility' => EventVisibility::Private]);

    $dto = new UpdateEventDto(
        title: $event->title,
        slug: $event->slug,
        visibility: EventVisibility::Public,
    );

    $action = resolve(UpdateEventAction::class);
    $updated = $action($event, $dto);

    expect($updated->visibility)->toBe(EventVisibility::Public);
});
