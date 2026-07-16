<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Facades\Health;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns a successful health check response with all configured checks passing', function () {
    Artisan::call('health:check');

    $response = $this->getJson('/health');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'finishedAt',
        'checkResults' => [
            '*' => [
                'name',
                'status',
            ],
        ],
    ]);

    $results = $response->json('checkResults');
    $names = collect($results)->pluck('name')->toArray();

    expect($names)->toContain('Database');
    expect($names)->toContain('Redis');
    expect($names)->toContain('Cache');

    foreach ($results as $check) {
        expect($check['status'])->toBe('ok');
    }
});

it('reports an unhealthy dependency when a check fails', function () {
    $failingCheck = new class extends Spatie\Health\Checks\Check
    {
        public function run(): Spatie\Health\Checks\Result
        {
            return Spatie\Health\Checks\Result::make()->failed('Simulated failure');
        }
    };
    $failingCheck->name('FailingCheck');

    Health::checks([$failingCheck]);
    Artisan::call('health:check');

    $response = $this->getJson('/health?fresh=1');

    $response->assertStatus(503);
    $results = collect($response->json('checkResults'));
    $failedCheck = $results->firstWhere('name', 'FailingCheck');
    expect($failedCheck['status'])->toBe('failed');
});

it('forces re-execution of checks when fresh parameter is present', function () {
    $initialTime = now();
    Illuminate\Support\Facades\Date::setTestNow($initialTime);

    Artisan::call('health:check');
    $firstResponse = $this->getJson('/health');
    $firstFinishedAt = $firstResponse->json('finishedAt');

    Illuminate\Support\Facades\Date::setTestNow($initialTime->copy()->addMinutes(5));

    $freshResponse = $this->getJson('/health?fresh=1');
    $freshFinishedAt = $freshResponse->json('finishedAt');

    expect($freshFinishedAt)->not->toBe($firstFinishedAt);
    expect($freshFinishedAt)->toBe($initialTime->copy()->addMinutes(5)->timestamp);

    Illuminate\Support\Facades\Date::setTestNow();
});
