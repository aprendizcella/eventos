<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Events\Waitlist\WaitlistEntryExpired;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;

final readonly class RollbackWaitlistReservationAction
{
    public function __invoke(WaitlistEntry $entry): WaitlistEntry
    {
        return DB::transaction(function () use ($entry): WaitlistEntry {
            /** @var WaitlistEntry $entry */
            $entry = WaitlistEntry::query()
                ->where('waitlist_entry_id', $entry->waitlist_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Solo hacemos rollback si está Reserved
            if ($entry->status !== WaitlistStatus::Reserved) {
                return $entry;
            }

            if ($entry->expires_at !== null && $entry->expires_at->isPast()) {
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
            } else {
                $entry->update([
                    'status' => WaitlistStatus::Notified,
                ]);
            }

            return $entry;
        });
    }
}
