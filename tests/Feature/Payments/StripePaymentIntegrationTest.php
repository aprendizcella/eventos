<?php

declare(strict_types=1);

use App\Actions\Payments\InitiatePaymentAction;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\TicketOrder;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('initiates payment intent and stores payment record locally', function (): void {
    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'total' => 150.00,
        'order_reference' => 'ORD-123456',
    ]);

    $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
    $mockGateway->shouldReceive('createPaymentIntent')
        ->once()
        ->with(Mockery::on(fn ($o) => $o->ticket_order_id === $order->ticket_order_id), 'stripe_intent_ORD-123456')
        ->andReturn([
            'client_secret' => 'pi_123_secret_abc',
            'provider_id' => 'pi_123',
        ]);

    $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

    $action = resolve(InitiatePaymentAction::class);
    $result = $action($order);

    expect($result)->toBe([
        'client_secret' => 'pi_123_secret_abc',
        'provider_id' => 'pi_123',
    ]);

    $this->assertDatabaseHas('payment', [
        'ticket_order_id' => $order->ticket_order_id,
        'provider_id' => 'pi_123',
        'status' => PaymentStatus::Pending->value,
        'amount' => 150.00,
    ]);
});

it('reuses existing pending payment intent client secret on retry', function (): void {
    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'total' => 89.99,
        'order_reference' => 'ORD-789012',
    ]);

    // Crear un pago pendiente previo
    Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'provider_id' => 'pi_existing_999',
        'status' => PaymentStatus::Pending,
        'amount' => 89.99,
    ]);

    $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
    $mockGateway->shouldReceive('retrievePaymentIntent')
        ->once()
        ->with('pi_existing_999')
        ->andReturn([
            'client_secret' => 'pi_existing_secret_xyz',
            'provider_id' => 'pi_existing_999',
            'status' => 'requires_payment_method',
        ]);

    $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

    $action = resolve(InitiatePaymentAction::class);
    $result = $action($order);

    expect($result)->toBe([
        'client_secret' => 'pi_existing_secret_xyz',
        'provider_id' => 'pi_existing_999',
    ]);

    // Verificar que no se creó otro registro de pago duplicado
    expect(Payment::query()->where('ticket_order_id', $order->ticket_order_id)->count())->toBe(1);
});
