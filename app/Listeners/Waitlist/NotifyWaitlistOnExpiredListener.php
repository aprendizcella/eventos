<?php

declare(strict_types=1);

namespace App\Listeners\Waitlist;

use App\Actions\Waitlist\NotifyWaitlistAction;
use App\Enums\WaitlistStatus;
use App\Events\Waitlist\WaitlistEntryExpired;
use App\Models\WaitlistEntry;

final readonly class NotifyWaitlistOnExpiredListener
{
    public function __construct(
        private NotifyWaitlistAction $notifyAction,
    ) {}

    public function handle(WaitlistEntryExpired $event): void
    {
        // Buscar el siguiente en la cola (Waiting) para el mismo tier (product_price_id)
        /** @var WaitlistEntry|null $nextEntry */
        $nextEntry = WaitlistEntry::query()
            ->where('product_price_id', $event->productPriceId)
            ->where('status', WaitlistStatus::Waiting)
            ->orderBy('waitlist_entry_id', 'asc')
            ->first();

        if ($nextEntry !== null) {
            ($this->notifyAction)($nextEntry);
        }
    }
}
