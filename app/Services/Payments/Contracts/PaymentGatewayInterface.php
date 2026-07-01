<?php

declare(strict_types=1);

namespace App\Services\Payments\Contracts;

use App\DataTransferObjects\Payments\RefundResponseDto;
use App\Models\Payment;
use App\Models\TicketOrder;

interface PaymentGatewayInterface
{
    /**
     * @return array{client_secret: string, provider_id: string}
     */
    public function createPaymentIntent(TicketOrder $order, string $idempotencyKey): array;

    /**
     * @return array{client_secret: string, provider_id: string, status: string}
     */
    public function retrievePaymentIntent(string $providerId): array;

    /**
     * Procesa un reembolso en la pasarela.
     */
    public function refund(Payment $payment, float $amount, string $idempotencyKey, ?string $reason = null): RefundResponseDto;
}
