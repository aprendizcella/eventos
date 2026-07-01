<?php

declare(strict_types=1);

use App\Actions\Payments\ProcessRefundAction;
use App\DataTransferObjects\Payments\RefundResponseDto;
use App\Enums\PaymentStatus;
use App\Enums\TicketOrderStatus;
use App\Models\Payment;
use App\Models\ProductPrice;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('performs a full refund, updates order status, and releases stock via listener', function (): void {
    // 1. Crear datos de prueba
    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'total' => 100.00,
        'status' => TicketOrderStatus::Completed,
    ]);

    /** @var ProductPrice $price */
    $price = ProductPrice::factory()->create([
        'quantity_sold' => 2,
    ]);

    TicketOrderItem::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'product_price_id' => $price->product_price_id,
        'quantity' => 2,
    ]);

    /** @var Payment $payment */
    $payment = Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'amount' => 100.00,
        'status' => PaymentStatus::Completed,
        'provider_id' => 'pi_test_refund',
    ]);

    // 2. Mockear el Gateway de Pagos
    $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
    $mockGateway->shouldReceive('refund')
        ->once()
        ->with(
            Mockery::on(fn ($p) => $p->payment_id === $payment->payment_id),
            100.00,
            Mockery::on(fn ($uuid) => Illuminate\Support\Str::isUuid($uuid)),
            'Customer request',
        )
        ->andReturn(new RefundResponseDto(
            providerRefundId: 're_test_full_123',
            status: 'succeeded',
            amount: 100.00,
        ));

    $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

    // 3. Ejecutar la acción
    $action = resolve(ProcessRefundAction::class);
    $refund = $action($payment, 100.00, 'Customer request');

    // 4. Assertions
    $refund->refresh();
    $payment->refresh();
    $order->refresh();
    $price->refresh();

    expect($refund->status)->toBe('completed');
    expect($refund->provider_id)->toBe('re_test_full_123');
    expect($payment->status)->toBe(PaymentStatus::Refunded);
    expect($order->status)->toBe(TicketOrderStatus::Refunded);

    // El stock de quantity_sold en el precio debe haberse decrementado de 2 a 0 debido al listener
    expect($price->quantity_sold)->toBe(0);
});

it('performs a partial refund and updates payment status but keeps order status completed', function (): void {
    // 1. Crear datos de prueba
    /** @var TicketOrder $order */
    $order = TicketOrder::factory()->create([
        'total' => 150.00,
        'status' => TicketOrderStatus::Completed,
    ]);

    /** @var ProductPrice $price */
    $price = ProductPrice::factory()->create([
        'quantity_sold' => 3,
    ]);

    TicketOrderItem::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'product_price_id' => $price->product_price_id,
        'quantity' => 3,
    ]);

    /** @var Payment $payment */
    $payment = Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'amount' => 150.00,
        'status' => PaymentStatus::Completed,
        'provider_id' => 'pi_test_partial_refund',
    ]);

    // 2. Mockear el Gateway de Pagos
    $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
    $mockGateway->shouldReceive('refund')
        ->once()
        ->with(
            Mockery::on(fn ($p) => $p->payment_id === $payment->payment_id),
            50.00,
            Mockery::on(fn ($uuid) => Illuminate\Support\Str::isUuid($uuid)),
            'Partial refund request',
        )
        ->andReturn(new RefundResponseDto(
            providerRefundId: 're_test_partial_456',
            status: 'succeeded',
            amount: 50.00,
        ));

    $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

    // 3. Ejecutar la acción
    $action = resolve(ProcessRefundAction::class);
    $refund = $action($payment, 50.00, 'Partial refund request');

    // 4. Assertions
    $refund->refresh();
    $payment->refresh();
    $order->refresh();
    $price->refresh();

    expect($refund->status)->toBe('completed');
    expect($refund->provider_id)->toBe('re_test_partial_456');
    expect($payment->status)->toBe(PaymentStatus::PartiallyRefunded);

    // La orden permanece en estado Completed (no cambia a Refunded)
    expect($order->status)->toBe(TicketOrderStatus::Completed);

    // El stock no se altera para reembolsos parciales
    expect($price->quantity_sold)->toBe(3);
});
