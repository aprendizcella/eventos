<?php

declare(strict_types=1);

namespace App\Listeners\Payments;

use App\Actions\Payments\AdjustPayoutAction;
use App\Events\Payments\RefundProcessed;

final readonly class AdjustPayoutOnRefundProcessed
{
    public function __construct(
        private AdjustPayoutAction $adjustPayoutAction,
    ) {}

    /**
     * Adjust or reverse the payout when a refund is processed.
     */
    public function handle(RefundProcessed $event): void
    {
        ($this->adjustPayoutAction)($event->refund);
    }
}
