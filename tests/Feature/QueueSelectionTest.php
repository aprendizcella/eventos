<?php

declare(strict_types=1);

use App\Jobs\Notifications\SendBulkEmailJob;
use App\Jobs\Payments\SendTicketEmailJob;
use Tests\TestCase;

uses(TestCase::class);

// =============================================================================
// Queue Selection Tests
// =============================================================================

it('sends ticket email jobs to the tickets queue', function (): void {
    $job = new SendTicketEmailJob(42);

    expect($job->queue)->toBe('tickets');
});

it('sends bulk email jobs to the emails queue', function (): void {
    $job = new SendBulkEmailJob(42);

    expect($job->queue)->toBe('emails');
});
