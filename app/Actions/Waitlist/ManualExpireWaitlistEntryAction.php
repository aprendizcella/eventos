<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Events\Waitlist\WaitlistEntryExpired;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;

final readonly class ManualExpireWaitlistEntryAction
{
    public function __invoke(WaitlistEntry $entry): WaitlistEntry
    {
        return DB::transaction(function () use ($entry): WaitlistEntry {
            /** @var WaitlistEntry $entry */
            $entry = WaitlistEntry::query()
                ->where('waitlist_entry_id', $entry->waitlist_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotencia: si ya está expirada o convertida, no hacer nada
            if (in_array($entry->status, [WaitlistStatus::Expired, WaitlistStatus::Converted], true)) {
                return $entry;
            }

            $entry->update([
                'status' => WaitlistStatus::Expired,
                'token' => null,
                'expires_at' => null,
            ]);

            DB::afterCommit(function () use ($entry): void {
                event(new WaitlistEntryExpired(
                    $entry->waitlist_entry_id,
                    $entry->product_price_id,
                    $entry->event_id,
                ));
            });

            return $entry;
        });
    }
}
