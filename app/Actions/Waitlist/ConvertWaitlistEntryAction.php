<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;

final readonly class ConvertWaitlistEntryAction
{
    public function __invoke(WaitlistEntry $entry): WaitlistEntry
    {
        return DB::transaction(function () use ($entry): WaitlistEntry {
            /** @var WaitlistEntry $entry */
            $entry = WaitlistEntry::query()
                ->where('waitlist_entry_id', $entry->waitlist_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status === WaitlistStatus::Converted) {
                return $entry;
            }

            $entry->update([
                'status' => WaitlistStatus::Converted,
            ]);

            return $entry;
        });
    }
}
