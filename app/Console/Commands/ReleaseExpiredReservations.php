<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
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
    public function handle(): int
    {
        $expiredOrders = \App\Models\TicketOrder::query()
            ->where('status', \App\Enums\TicketOrderStatus::Reserved)
            ->where('reserved_until', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredOrders as $order) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
                $order->update([
                    'status' => \App\Enums\TicketOrderStatus::Expired,
                    'reserved_until' => null,
                ]);

                activity()
                    ->performedOn($order)
                    ->useLog('ticket_order')
                    ->log('expired');
            });
            $count++;
        }

        $this->info("Expired {$count} ticket reservations.");

        return Command::SUCCESS;
    }
}
