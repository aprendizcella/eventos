<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\TicketOrderStatus;
use App\Models\Payment;
use App\Models\TicketOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.stripe.webhook.secret' => 'whsec_test']);
});

function generateStripeSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    return "t={$timestamp},v1={$signature}";
}

it('handles payment_intent.succeeded webhook event and confirms order', function (): void {
    Event::fake();

    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'status' => TicketOrderStatus::Reserved,
    ]);

    /** @var Payment $payment */
    $payment = Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'provider_id' => 'pi_test_succeeded',
        'status' => PaymentStatus::Pending,
    ]);

    $payload = json_encode([
        'id' => 'evt_test_succeeded_999',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_succeeded',
                'status' => 'succeeded',
            ],
        ],
    ]);

    $sigHeader = generateStripeSignature($payload, 'whsec_test');

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'processed']);

    // Verificar que el pago y la orden fueron actualizados
    $payment->refresh();
    $order->refresh();

    expect($payment->status)->toBe(PaymentStatus::Completed);
    expect($order->status)->toBe(TicketOrderStatus::Completed);

    // Verificar que se registró la idempotencia
    $this->assertDatabaseHas('processed_webhook_event', [
        'event_id' => 'evt_test_succeeded_999',
    ]);

    Event::assertDispatched(App\Events\Payments\PaymentCompleted::class);
});

it('ignores already processed webhook events to ensure idempotency', function (): void {
    Event::fake();

    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'status' => TicketOrderStatus::Reserved,
    ]);

    /** @var Payment $payment */
    $payment = Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'provider_id' => 'pi_test_double',
        'status' => PaymentStatus::Pending,
    ]);

    // Registrar previamente el evento en la tabla de idempotencia
    DB::table('processed_webhook_event')->insert([
        'event_id' => 'evt_test_duplicate_111',
        'created_at' => now(),
    ]);

    $payload = json_encode([
        'id' => 'evt_test_duplicate_111',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_double',
                'status' => 'succeeded',
            ],
        ],
    ]);

    $sigHeader = generateStripeSignature($payload, 'whsec_test');

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'ignored']);

    // El estado no debería cambiar porque se ignoró el evento
    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Pending);

    Event::assertNotDispatched(App\Events\Payments\PaymentCompleted::class);
});

it('rejects webhooks with invalid signatures', function (): void {
    $payload = json_encode([
        'id' => 'evt_test_invalid_sig',
        'type' => 'payment_intent.succeeded',
    ]);

    $response = $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
        'Stripe-Signature' => 't=123,v1=invalid_hmac_signature',
    ]);

    $response->assertStatus(400);
    $response->assertJsonStructure(['error']);
});
