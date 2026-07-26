<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class WebhookDispatcher
{
    public function send(WebhookDelivery $delivery, Webhook $webhook): Response
    {
        $signature = 'v1='.hash_hmac('sha256', $delivery->envelope, $webhook->secret);

        return $this->request()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-HiEvents-Event' => $delivery->event,
                'X-HiEvents-Delivery-Id' => $delivery->delivery_id,
                'X-HiEvents-Signature' => $signature,
            ])
            ->withBody($delivery->envelope, 'application/json')
            ->post($webhook->endpoint);
    }

    private function request(): PendingRequest
    {
        return Http::connectTimeout(5)
            ->timeout(10)
            ->withOptions(['allow_redirects' => false]);
    }
}
