<?php

declare(strict_types=1);

use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('explicit global marker is logged as global with null organizer', function () {
    activity()
        ->withProperties(['is_global' => true])
        ->log('explicit global message');

    $log = Activity::query()->orderBy('id', 'desc')->first();

    expect($log)->not->toBeNull()
        ->and((bool) $log->is_global)->toBeTrue()
        ->and($log->organizer_id)->toBeNull()
        ->and($log->properties->has('is_global'))->toBeFalse(); // Stripped
});

test('explicit organizer is logged with correct organizer_id', function () {
    $organizer = Organizer::factory()->create();

    activity()
        ->withProperties(['organizer_id' => $organizer->id])
        ->log('explicit organizer message');

    $log = Activity::query()->orderBy('id', 'desc')->first();

    expect($log)->not->toBeNull()
        ->and((bool) $log->is_global)->toBeFalse()
        ->and($log->organizer_id)->toBe($organizer->id)
        ->and($log->properties->has('organizer_id'))->toBeFalse(); // Stripped
});

test('current tenant context automatically populates organizer_id', function () {
    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    activity()->log('contextual message');

    $log = Activity::query()->orderBy('id', 'desc')->first();

    expect($log)->not->toBeNull()
        ->and((bool) $log->is_global)->toBeFalse()
        ->and($log->organizer_id)->toBe($organizer->id);

    Organizer::forgetCurrent();
});

test('conflicting explicit metadata throws exception and does not write', function () {
    $organizer = Organizer::factory()->create();

    expect(fn () => activity()
        ->withProperties(['is_global' => true, 'organizer_id' => $organizer->id])
        ->log('conflicting-explicit'),
    )->toThrow(InvalidArgumentException::class);

    expect(Activity::query()->where('description', 'conflicting-explicit')->exists())->toBeFalse();
});

test('conflict with current tenant context throws exception', function () {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $organizerA->makeCurrent();

    expect(fn () => activity()
        ->withProperties(['organizer_id' => $organizerB->id])
        ->log('conflicting-tenant'),
    )->toThrow(InvalidArgumentException::class);

    Organizer::forgetCurrent();
    expect(Activity::query()->where('description', 'conflicting-tenant')->exists())->toBeFalse();
});

test('missing context logs as global legacy row', function () {
    activity()->log('no context');

    $log = Activity::query()->orderBy('id', 'desc')->first();

    expect($log)->not->toBeNull()
        ->and((bool) $log->is_global)->toBeFalse()
        ->and($log->organizer_id)->toBeNull();
});

test('no context leakage across multiple logging runs', function () {
    $organizer7 = Organizer::factory()->create();
    $organizer8 = Organizer::factory()->create();

    // Run 1: Organizer 7
    $organizer7->makeCurrent();
    activity()->log('msg 7');
    Organizer::forgetCurrent();

    // Run 2: Global
    activity()->withProperties(['is_global' => true])->log('msg global');

    // Run 3: Organizer 8
    $organizer8->makeCurrent();
    activity()->log('msg 8');
    Organizer::forgetCurrent();

    // Run 4: Unclassified legacy
    activity()->log('msg legacy');

    $logs = Activity::query()
        ->whereIn('description', ['msg 7', 'msg global', 'msg 8', 'msg legacy'])
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(4);

    expect($logs[0]->organizer_id)->toBe($organizer7->id)
        ->and((bool) $logs[0]->is_global)->toBeFalse();

    expect($logs[1]->organizer_id)->toBeNull()
        ->and((bool) $logs[1]->is_global)->toBeTrue();

    expect($logs[2]->organizer_id)->toBe($organizer8->id)
        ->and((bool) $logs[2]->is_global)->toBeFalse();

    expect($logs[3]->organizer_id)->toBeNull()
        ->and((bool) $logs[3]->is_global)->toBeFalse();
});

test('buffered tenant capture preserves classification', function () {
    config(['activitylog.buffer.enabled' => true]);

    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    activity()->log('buffered owned message');

    Organizer::forgetCurrent();

    // Flush using Spatie buffer service
    $buffer = resolve(Spatie\Activitylog\Support\ActivityBuffer::class);
    $buffer->flush();

    $log = Activity::query()->orderBy('id', 'desc')->first();

    expect($log)->not->toBeNull()
        ->and((bool) $log->is_global)->toBeFalse()
        ->and($log->organizer_id)->toBe($organizer->id);

    config(['activitylog.buffer.enabled' => false]);
});
