<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\DataTransferObjects\Payments\RefundResponseDto;
use App\Models\Payment;
use App\Models\TicketOrder;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;
use Stripe\StripeClient;

final readonly class StripeGateway implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        /** @var string $secret */
        $secret = config('services.stripe.secret', 'sk_test_mock');
        $this->stripe = new StripeClient($secret);
    }

    public function createPaymentIntent(TicketOrder $order, string $idempotencyKey): array
    {
        $cents = intval(round($order->total * 100));

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $cents,
            'currency' => strtolower($order->currency ?? 'usd'),
            'metadata' => [
                'order_reference' => $order->order_reference,
                'ticket_order_id' => (string) $order->ticket_order_id,
            ],
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        return [
            'client_secret' => (string) $intent->client_secret,
            'provider_id' => $intent->id,
        ];
    }

    public function retrievePaymentIntent(string $providerId): array
    {
        $intent = $this->stripe->paymentIntents->retrieve($providerId);

        return [
            'client_secret' => (string) $intent->client_secret,
            'provider_id' => $intent->id,
            'status' => $intent->status,
        ];
    }

    public function refund(Payment $payment, float $amount, string $idempotencyKey, ?string $reason = null): RefundResponseDto
    {
        $cents = intval(round($amount * 100));

        if ($payment->provider_id === null) {
            throw new InvalidArgumentException('Payment provider_id cannot be null for refund.');
        }

        $options = [
            'payment_intent' => $payment->provider_id,
            'amount' => $cents,
        ];

        if ($reason !== null) {
            $options['reason'] = $reason;
        }

        $refund = $this->stripe->refunds->create($options, [
            'idempotency_key' => $idempotencyKey,
        ]);

        return new RefundResponseDto(
            providerRefundId: $refund->id,
            status: $refund->status ?? 'succeeded',
            amount: $amount,
        );
    }
}
