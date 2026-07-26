<?php

declare(strict_types=1);

namespace App\Console\Commands\Webhooks;

use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

final class PurgeExpiredWebhookDeliveriesCommand extends Command
{
    protected $signature = 'webhooks:purge-expired-deliveries';

    protected $description = 'Purge terminal webhook deliveries after their retention period';

    public function handle(): int
    {
        $deleted = WebhookDelivery::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->forceDelete();

        $this->info("Purged {$deleted} expired webhook deliveries.");

        return self::SUCCESS;
    }
}
