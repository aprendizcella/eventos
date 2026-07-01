<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Products;

use App\Enums\PricingMode;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;

final readonly class CreateProductDto
{
    /**
     * @param array<int, array{
     *     name: string,
     *     price: float,
     *     capacity: int|null,
     *     sales_start_at: \Carbon\Carbon|null,
     *     sales_end_at: \Carbon\Carbon|null
     * }> $prices
     */
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $description,
        public ProductType $type,
        public PricingMode $pricing_mode,
        public ProductStatus $status,
        public ProductVisibility $visibility,
        public ?string $password,
        public int $min_qty,
        public int $max_qty,
        public int $sort_order,
        public array $prices,
    ) {}
}
