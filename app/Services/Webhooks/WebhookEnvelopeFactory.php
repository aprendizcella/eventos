<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Models\WebhookDelivery;
use LogicException;

final class WebhookEnvelopeFactory
{
    public function make(WebhookDelivery $delivery): string
    {
        $payment = $delivery->payment;

        if ($payment === null) {
            throw new LogicException('Webhook delivery payment must exist before building the envelope.');
        }

        return json_encode([
            'version' => $delivery->version,
            'event' => $delivery->event,
            'delivery_id' => $delivery->delivery_id,
            'occurred_at' => $delivery->occurred_at->utc()->toISOString(),
            'organizer_id' => $delivery->organizer_id,
            'data' => [
                'payment_id' => $delivery->payment_id,
                'order_id' => $payment->ticket_order_id,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
