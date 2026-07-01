<?php

declare(strict_types=1);

namespace App\Events\Payments;

use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Refund $refund,
    ) {}
}
