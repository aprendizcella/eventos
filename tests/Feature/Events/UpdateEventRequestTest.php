<?php

declare(strict_types=1);

use App\Http\Requests\Events\UpdateEventRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('has required validation rules', function (): void {
    $request = new UpdateEventRequest;

    expect($request->rules())->toHaveKey('title')
        ->and($request->rules())->toHaveKey('slug');
});

it('converts to DTO', function (): void {
    $request = new UpdateEventRequest;
    $request->merge([
        'title' => 'Updated Title',
        'slug' => 'updated-slug',
        'description' => '<p>New desc</p>',
    ]);

    $validator = resolve('validator')->make($request->all(), [
        'title' => ['required', 'string'],
        'slug' => ['required', 'string'],
        'description' => ['nullable', 'string'],
    ]);
    $request->setValidator($validator);

    $dto = $request->toDto();

    expect($dto->title)->toBe('Updated Title')
        ->and($dto->slug)->toBe('updated-slug')
        ->and($dto->description)->toBe('<p>New desc</p>');
});

it('handles nullable fields in DTO', function (): void {
    $request = new UpdateEventRequest;
    $request->merge([
        'title' => 'Test',
        'slug' => 'test',
    ]);

    $validator = resolve('validator')->make($request->all(), [
        'title' => ['required', 'string'],
        'slug' => ['required', 'string'],
    ]);
    $request->setValidator($validator);

    $dto = $request->toDto();

    expect($dto->description)->toBeNull()
        ->and($dto->startsAt)->toBeNull()
        ->and($dto->endsAt)->toBeNull()
        ->and($dto->categoryId)->toBeNull()
        ->and($dto->venueId)->toBeNull()
        ->and($dto->visibility)->toBeNull();
});
