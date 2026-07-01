<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Payments;

final readonly class RefundResponseDto
{
    public function __construct(
        public string $providerRefundId,
        public string $status,
        public float $amount,
    ) {}
}
