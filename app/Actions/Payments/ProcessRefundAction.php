<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Enums\TicketOrderStatus;
use App\Exceptions\Orders\OrderException;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ProcessRefundAction
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
    ) {}

    public function __invoke(Payment $payment, float $amount, ?string $reason = null): Refund
    {
        if ($amount <= 0) {
            throw OrderException::invalidStatus(__('Refund amount must be greater than zero.'));
        }

        $totalRefunded = $payment->getTotalRefundedAmount();
        $remaining = (float) bcsub((string) $payment->amount, (string) $totalRefunded, 2);

        if ($amount > $remaining) {
            throw OrderException::invalidStatus(__('Refund amount exceeds remaining refundable balance.'));
        }

        return DB::transaction(function () use ($payment, $amount, $reason): Refund {
            $uuid = Str::uuid()->toString();

            /** @var Refund $refund */
            $refund = $payment->refunds()->create([
                'amount' => $amount,
                'idempotency_key' => $uuid,
                'status' => 'pending',
                'reason' => $reason,
                'provider_id' => null,
            ]);

            try {
                $responseDto = $this->gateway->refund($payment, $amount, $uuid, $reason);

                $refund->update([
                    'status' => 'completed',
                    'provider_id' => $responseDto->providerRefundId,
                ]);

                // Recalcular estado del pago y de la orden
                $newTotalRefunded = $payment->getTotalRefundedAmount();

                if ($newTotalRefunded >= $payment->amount) {
                    $payment->update(['status' => PaymentStatus::Refunded]);

                    if ($payment->ticketOrder !== null) {
                        $payment->ticketOrder->update(['status' => TicketOrderStatus::Refunded]);
                    }
                } else {
                    $payment->update(['status' => PaymentStatus::PartiallyRefunded]);
                }

                // Disparar evento de dominio para efectos secundarios (ej: correos, liberación de stock si aplica)
                event(new \App\Events\Payments\RefundProcessed($refund));

                return $refund;
            } catch (Exception $e) {
                $refund->update([
                    'status' => 'failed',
                ]);

                throw $e;
            }
        });
    }
}
