<?php

declare(strict_types=1);

use App\Actions\Events\CreateEventAction;
use App\DataTransferObjects\Events\CreateEventDto;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an event with minimum fields via action', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateEventDto(
        organizerId: $organizer->getKey(),
        title: 'Test Event',
        slug: 'test-event',
    );

    $action = resolve(CreateEventAction::class);
    $event = $action($dto);

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->title)->toBe('Test Event')
        ->and($event->slug)->toBe('test-event')
        ->and($event->organizer_id)->toBe($organizer->getKey())
        ->and($event->status)->toBe(EventStatus::Draft)
        ->and($event->visibility)->toBe(EventVisibility::Private);
});

it('creates an event with all fields', function (): void {
    $organizer = Organizer::factory()->create();
    $startsAt = now()->addWeek();
    $endsAt = now()->addWeek()->addHours(2);

    $dto = new CreateEventDto(
        organizerId: $organizer->getKey(),
        title: 'Full Event',
        slug: 'full-event',
        description: '<p>A description</p>',
        startsAt: $startsAt,
        endsAt: $endsAt,
        categoryId: null,
        venueId: null,
        visibility: EventVisibility::Public,
    );

    $action = resolve(CreateEventAction::class);
    $event = $action($dto);

    expect($event->title)->toBe('Full Event')
        ->and($event->description)->toBe('<p>A description</p>')
        ->and($event->starts_at)->not->toBeNull()
        ->and($event->visibility)->toBe(EventVisibility::Public);
});

it('sanitizes description by stripping script tags', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateEventDto(
        organizerId: $organizer->getKey(),
        title: 'XSS Test',
        slug: 'xss-test',
        description: '<p>Hello</p><script>alert(1)</script>',
    );

    $action = resolve(CreateEventAction::class);
    $event = $action($dto);

    expect($event->description)->not->toContain('<script>')
        ->and($event->description)->toContain('<p>');
});

it('preserves safe HTML tags in description', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateEventDto(
        organizerId: $organizer->getKey(),
        title: 'Safe HTML',
        slug: 'safe-html',
        description: '<p>Hello <strong>world</strong></p>',
    );

    $action = resolve(CreateEventAction::class);
    $event = $action($dto);

    expect($event->description)->toContain('<p>')
        ->and($event->description)->toContain('<strong>');
});

it('strips event handlers from description', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateEventDto(
        organizerId: $organizer->getKey(),
        title: 'Handler Test',
        slug: 'handler-test',
        description: '<p onclick="alert(1)">Click me</p>',
    );

    $action = resolve(CreateEventAction::class);
    $event = $action($dto);

    expect($event->description)->not->toContain('onclick')
        ->and($event->description)->toContain('<p>');
});
