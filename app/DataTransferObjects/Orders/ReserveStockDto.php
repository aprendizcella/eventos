<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Orders;

final readonly class ReserveStockDto
{
    /**
     * @param  array<int, ReserveStockItemDto>  $items
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?int $promoCodeId = null,
        public array $items = [],
        public ?string $waitlistToken = null,
    ) {}
}
