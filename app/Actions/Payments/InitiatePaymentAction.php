<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\TicketOrder;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Exception;

final readonly class InitiatePaymentAction
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @return array{client_secret: string, provider_id: string}
     */
    public function __invoke(TicketOrder $order): array
    {
        /** @var Payment|null $existingPayment */
        $existingPayment = Payment::query()
            ->where('ticket_order_id', $order->ticket_order_id)
            ->where('status', PaymentStatus::Pending)
            ->first();

        if ($existingPayment !== null && $existingPayment->provider_id !== null) {
            $intentData = $this->gateway->retrievePaymentIntent($existingPayment->provider_id);

            return [
                'client_secret' => $intentData['client_secret'],
                'provider_id' => $existingPayment->provider_id,
            ];
        }

        // Crear registro local primero
        /** @var Payment $payment */
        $payment = Payment::query()->create([
            'ticket_order_id' => $order->ticket_order_id,
            'provider_id' => null,
            'payment_method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Pending,
            'amount' => $order->total,
            'currency' => $order->currency ?? 'USD',
        ]);

        $idempotencyKey = 'stripe_intent_'.$order->order_reference;

        try {
            $intentData = $this->gateway->createPaymentIntent($order, $idempotencyKey);

            $payment->update([
                'provider_id' => $intentData['provider_id'],
            ]);

            return $intentData;
        } catch (Exception $e) {
            $payment->update([
                'status' => PaymentStatus::Failed,
            ]);

            throw $e;
        }
    }
}
