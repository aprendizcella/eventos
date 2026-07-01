<?php

declare(strict_types=1);

namespace App\DataTransferObjects\PromoCodes;

use App\Enums\PromoCodeType;
use Carbon\CarbonInterface;

final readonly class CreatePromoCodeDto
{
    public function __construct(
        public string $code,
        public PromoCodeType $type,
        public float $value,
        public ?int $max_uses = null,
        public ?CarbonInterface $start_at = null,
        public ?CarbonInterface $end_at = null,
        public string $status = 'active',
    ) {}
}
