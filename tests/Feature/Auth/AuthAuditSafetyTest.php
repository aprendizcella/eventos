<?php

declare(strict_types=1);

use App\Actions\Auth\RecordAuthActivityAction;
use Illuminate\Contracts\Config\Repository;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Activitylog\Support\ActivityLogStatus;
use Spatie\Activitylog\Support\CauserResolver;
use Spatie\Activitylog\Support\PendingActivityLog;
use Tests\TestCase;

uses(TestCase::class);

it('never breaks the auth response when audit logging fails and the sanitized context is unserializable', function (): void {
    // Force the Activitylog writer to fail so the Action's catch path runs.
    $failingLogger = new class(app(Repository::class), app(ActivityLogStatus::class), app(CauserResolver::class)) extends ActivityLogger
    {
        public function log(string $description): ?Activity
        {
            throw new RuntimeException('audit writer is unavailable');
        }
    };

    $this->app->bind(PendingActivityLog::class, static fn () => new PendingActivityLog(
        $failingLogger,
        app(ActivityLogStatus::class),
    ));

    $action = app(RecordAuthActivityAction::class);

    // An allowlisted key carrying an unserializable value exercises the catch
    // path's json_encode: with JSON_THROW_ON_ERROR this would propagate and break
    // the auth response; without it the failure is reported and swallowed.
    $resource = fopen('php://memory', 'r');

    // Must NOT throw, even though the logger fails and the context cannot be
    // JSON-serialized.
    $action('login', null, null, ['outcome' => $resource]);

    expect(true)->toBeTrue();

    fclose($resource);
});
