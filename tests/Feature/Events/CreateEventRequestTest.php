<?php

declare(strict_types=1);

use App\Http\Requests\Events\CreateEventRequest;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('validates required fields', function (): void {
    $request = new CreateEventRequest;

    expect($request->rules())->toHaveKey('title')
        ->and($request->rules())->toHaveKey('slug');
});

it('validates slug uniqueness', function (): void {
    Event::factory()->create(['slug' => 'existing-slug']);

    $request = new CreateEventRequest;
    $request->merge([
        'title' => 'Test',
        'slug' => 'existing-slug',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slug'))->toBeTrue();
});

it('converts to DTO', function (): void {
    $organizer = Organizer::factory()->create();

    $request = new CreateEventRequest;
    $request->merge([
        'title' => 'Test Event',
        'slug' => 'test-event',
        'description' => '<p>Desc</p>',
        'visibility' => 'public',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());
    $request->setValidator($validator);

    $dto = $request->toDto($organizer->getKey());

    expect($dto->organizerId)->toBe($organizer->getKey())
        ->and($dto->title)->toBe('Test Event')
        ->and($dto->slug)->toBe('test-event')
        ->and($dto->description)->toBe('<p>Desc</p>')
        ->and($dto->visibility->value)->toBe('public');
});

it('applies default visibility when not provided', function (): void {
    $organizer = Organizer::factory()->create();

    $request = new CreateEventRequest;
    $request->merge([
        'title' => 'Test',
        'slug' => 'test',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());
    $request->setValidator($validator);

    $dto = $request->toDto($organizer->getKey());

    expect($dto->visibility->value)->toBe('private');
});
