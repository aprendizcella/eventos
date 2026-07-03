<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Orders\ExpireTicketOrderAction;
use App\Enums\TicketOrderStatus;
use App\Models\TicketOrder;
use Illuminate\Console\Command;

final class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:release-expired-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release ticket order reservations that exceeded their 10-minute TTL';

    /**
     * Execute the console command.
     */
    public function handle(ExpireTicketOrderAction $expireTicketOrderAction): int
    {
        $expiredOrders = TicketOrder::query()
            ->where('status', TicketOrderStatus::Reserved)
            ->where('reserved_until', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredOrders as $order) {
            $expireTicketOrderAction($order);
            $count++;
        }

        $this->info("Expired {$count} ticket reservations.");

        return Command::SUCCESS;
    }
}
