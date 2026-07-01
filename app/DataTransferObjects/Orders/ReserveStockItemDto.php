<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Orders;

final readonly class ReserveStockItemDto
{
    public function __construct(
        public int $productPriceId,
        public int $quantity,
    ) {}
}
