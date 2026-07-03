<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Events\Waitlist\WaitlistEntryExpired;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;

final readonly class ExpireWaitlistEntriesAction
{
    public function __invoke(): int
    {
        $expiredEntries = WaitlistEntry::query()
            ->where('status', WaitlistStatus::Notified)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredEntries as $entry) {
            DB::transaction(function () use ($entry) {
                /** @var WaitlistEntry $entry */
                $entry = WaitlistEntry::query()
                    ->where('waitlist_entry_id', $entry->waitlist_entry_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Validar nuevamente el estado bajo lock
                if ($entry->status !== WaitlistStatus::Notified) {
                    return;
                }

                $entry->update([
                    'status' => WaitlistStatus::Expired,
                ]);

                DB::afterCommit(function () use ($entry) {
                    event(new WaitlistEntryExpired(
                        $entry->waitlist_entry_id,
                        $entry->product_price_id,
                        $entry->event_id,
                    ));
                });
            });
            $count++;
        }

        return $count;
    }
}
