<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Orders\ConfirmTicketOrderAction;
use App\Enums\PaymentStatus;
use App\Enums\TicketOrderStatus;
use App\Events\Payments\PaymentCompleted;
use App\Events\Payments\PaymentFailed;
use App\Events\Payments\RefundProcessed;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Webhook;

final readonly class HandleStripeWebhookAction
{
    public function __construct(
        private ConfirmTicketOrderAction $confirmTicketOrderAction,
    ) {}

    public function __invoke(string $payload, string $sigHeader): string
    {
        /** @var string $endpointSecret */
        $endpointSecret = config('services.stripe.webhook.secret', 'whsec_mock');

        // Validar firma sobre el body crudo
        $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

        // Idempotencia del webhook: verificar si ya fue procesado
        $alreadyProcessed = DB::table('processed_webhook_event')
            ->where('event_id', $event->id)
            ->exists();

        if ($alreadyProcessed) {
            return 'ignored';
        }

        return DB::transaction(function () use ($event): string {
            // Registrar idempotencia
            DB::table('processed_webhook_event')->insert([
                'event_id' => $event->id,
                'created_at' => now(),
            ]);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    /** @var \Stripe\PaymentIntent $paymentIntent */
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentSucceeded($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    /** @var \Stripe\PaymentIntent $paymentIntent */
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);
                    break;

                case 'charge.refunded':
                    /** @var \Stripe\Charge $charge */
                    $charge = $event->data->object;
                    $this->handleChargeRefunded($charge);
                    break;

                default:
                    // Ignorar otros tipos de eventos de Stripe
                    break;
            }

            return 'processed';
        });
    }

    private function handlePaymentIntentSucceeded(\Stripe\StripeObject $paymentIntent): void
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()->where('provider_id', $paymentIntent->id)->first();

        if ($payment !== null && $payment->ticketOrder !== null) {
            $payment->update(['status' => PaymentStatus::Completed]);

            // Consolidar el stock y confirmar la orden (Frontera de estado)
            ($this->confirmTicketOrderAction)($payment->ticketOrder);

            // Disparar evento para efectos secundarios
            event(new PaymentCompleted($payment));
        }
    }

    private function handlePaymentIntentFailed(\Stripe\StripeObject $paymentIntent): void
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()->where('provider_id', $paymentIntent->id)->first();

        if ($payment !== null) {
            $payment->update(['status' => PaymentStatus::Failed]);
            event(new PaymentFailed($payment));
        }
    }

    private function handleChargeRefunded(\Stripe\StripeObject $charge): void
    {
        /** @var \Stripe\Charge $charge */
        /** @var Payment|null $payment */
        $payment = Payment::query()->where('provider_id', $charge->payment_intent)->first();

        if ($payment === null) {
            return;
        }

        // Sincronizar reembolsos creados externamente desde el panel de Stripe
        if ($charge->refunds !== null) {
            /** @var \Stripe\Refund $stripeRefund */
            foreach ($charge->refunds->data as $stripeRefund) {
                $exists = Refund::query()->where('provider_id', $stripeRefund->id)->exists();

                if (!$exists) {
                    $uuid = Str::uuid()->toString();
                    /** @var Refund $refund */
                    $refund = $payment->refunds()->create([
                        'amount' => $stripeRefund->amount / 100.0,
                        'idempotency_key' => $uuid,
                        'status' => 'completed',
                        'reason' => $stripeRefund->reason,
                        'provider_id' => $stripeRefund->id,
                    ]);

                    event(new RefundProcessed($refund));
                }
            }
        }

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
    }
}
