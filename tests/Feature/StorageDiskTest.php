<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('uses local disk by default when s3 is not explicitly configured', function () {
    $defaultDisk = config('filesystems.default');
    expect($defaultDisk)->toBe('local');
});

it('s3 disk is available and configurable via env', function () {
    config(['filesystems.default' => 's3']);

    expect(config('filesystems.default'))->toBe('s3');

    $s3Config = config('filesystems.disks.s3');
    expect($s3Config)->toBeArray();
    expect($s3Config)->toHaveKey('driver', 's3');
});

it('can write and read assets through the s3 disk fake', function () {
    Storage::fake('s3');

    Storage::disk('s3')->put('events/example.txt', 'asset contents');

    expect(Storage::disk('s3')->get('events/example.txt'))->toBe('asset contents');
    Storage::disk('s3')->assertExists('events/example.txt');
});
