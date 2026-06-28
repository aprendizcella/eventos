<?php

declare(strict_types=1);

use App\Actions\Events\PublishEventAction;
use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('publishes a draft event with required fields', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'title' => 'Ready Event',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Description</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);
    $published = $action($event);

    expect($published->status)->toBe(EventStatus::Published);
});

it('publishes a paused event', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Paused,
        'title' => 'Paused Event',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Description</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);
    $published = $action($event);

    expect($published->status)->toBe(EventStatus::Published);
});

it('rejects publish when starts_at is missing', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'title' => 'No Date',
        'starts_at' => null,
        'description' => '<p>Description</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects publish when description is empty', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'title' => 'No Desc',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => null,
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects publish when title is empty', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'title' => '',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects publish from cancelled status', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Cancelled,
        'title' => 'Cancelled',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('logs activity on publish', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'title' => 'Logged Event',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);
    $action($event);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Event::class)
        ->where('subject_id', $event->event_id)
        ->where('description', 'published')
        ->first();

    expect($activity)->not->toBeNull();
});

it('rejects publish when category is missing', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => null,
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects publish when venue is missing', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => null,
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects publish when end date is before start date', function (): void {
    $start = now()->addWeek();

    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => $start,
        'ends_at' => $start->copy()->subHour(),
        'description' => '<p>Desc</p>',
        'category_id' => Category::factory(),
        'venue_id' => Venue::factory(),
    ]);

    $action = resolve(PublishEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});
