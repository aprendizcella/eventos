<?php

declare(strict_types=1);

use App\Http\Requests\Organizers\CreateOrganizerRequest;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('validates required fields', function (): void {
    $request = new CreateOrganizerRequest;

    expect($request->rules())->toHaveKey('name')
        ->and($request->rules())->toHaveKey('slug');
});

it('validates slug uniqueness', function (): void {
    Organizer::query()->create(['name' => 'Test', 'slug' => 'existing-slug']);

    $request = new CreateOrganizerRequest;
    $request->merge([
        'name' => 'New Organizer',
        'slug' => 'existing-slug',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('slug'))->toBeTrue();
});

it('validates domain uniqueness when provided', function (): void {
    Organizer::query()->create(['name' => 'Test', 'slug' => 'test', 'domain' => 'existing.com']);

    $request = new CreateOrganizerRequest;
    $request->merge([
        'name' => 'New Organizer',
        'slug' => 'new-slug',
        'domain' => 'existing.com',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('domain'))->toBeTrue();
});

it('converts to DTO', function (): void {
    $request = new CreateOrganizerRequest;
    $request->merge([
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'domain' => 'test.example.com',
        'settings' => ['theme' => 'dark'],
        'status' => 'active',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());
    $request->setValidator($validator);

    $dto = $request->toDto();

    expect($dto->name)->toBe('Test Organizer')
        ->and($dto->slug)->toBe('test-organizer')
        ->and($dto->domain)->toBe('test.example.com')
        ->and($dto->settings)->toBe(['theme' => 'dark'])
        ->and($dto->status)->toBe('active');
});
