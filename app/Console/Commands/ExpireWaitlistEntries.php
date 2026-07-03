<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Waitlist\ExpireWaitlistEntriesAction;
use Illuminate\Console\Command;

final class ExpireWaitlistEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-waitlist-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire waitlist entries that exceeded their 24-hour TTL';

    /**
     * Execute the console command.
     */
    public function handle(ExpireWaitlistEntriesAction $action): int
    {
        $count = $action();

        $this->info("Expired {$count} waitlist invitations.");

        return Command::SUCCESS;
    }
}
