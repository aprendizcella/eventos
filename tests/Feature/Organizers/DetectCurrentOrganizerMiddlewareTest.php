<?php

declare(strict_types=1);

use App\Http\Middleware\DetectCurrentOrganizer;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('sets current organizer from route parameter', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);

    $request = Request::create('/organizers/test', 'GET');
    $request->attributes->set('current_organizer', $organizer);

    $middleware = new DetectCurrentOrganizer;
    $response = $middleware->handle($request, function ($req) {
        return new Response;
    });

    expect($request->attributes->get('current_organizer'))->toBe($organizer);
});

it('passes through when no organizer in route', function (): void {
    $request = Request::create('/dashboard', 'GET');

    $middleware = new DetectCurrentOrganizer;
    $response = $middleware->handle($request, function ($req) {
        return new Response;
    });

    expect($request->attributes->get('current_organizer'))->toBeNull();
});
