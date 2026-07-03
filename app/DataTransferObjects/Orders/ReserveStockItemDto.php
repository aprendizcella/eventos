<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Orders;

final readonly class ReserveStockItemDto
{
    /**
     * @param  array<int, array<string, mixed>>|null  $customAnswersStaging
     */
    public function __construct(
        public int $productPriceId,
        public int $quantity,
        public ?array $customAnswersStaging = null,
    ) {}
}
