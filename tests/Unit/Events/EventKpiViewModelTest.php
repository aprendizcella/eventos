<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Enums\AttendeeStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketOrderStatus;
use App\Enums\WaitlistStatus;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Refund;
use App\Models\TicketOrder;
use App\Models\WaitlistEntry;
use App\ViewModels\Events\EventKpiViewModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates net revenue by subtracting completed refunds from completed payments', function (): void {
    $event = Event::factory()->create();

    // Orden 1: Completada con 100 de pago y reembolso de 20 completado
    $order1 = TicketOrder::factory()->create(['event_id' => $event->event_id, 'status' => TicketOrderStatus::Completed]);
    $payment1 = Payment::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'amount' => 100.0,
        'status' => PaymentStatus::Completed,
    ]);
    Refund::factory()->create([
        'payment_id' => $payment1->payment_id,
        'amount' => 20.0,
        'status' => 'completed',
    ]);

    // Orden 2: Completada con 50 de pago, sin reembolsos
    $order2 = TicketOrder::factory()->create(['event_id' => $event->event_id, 'status' => TicketOrderStatus::Completed]);
    Payment::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'amount' => 50.0,
        'status' => PaymentStatus::Completed,
    ]);

    // Orden 3: Cancelada con 30 de pago, no debe sumarse
    $order3 = TicketOrder::factory()->create(['event_id' => $event->event_id, 'status' => TicketOrderStatus::Cancelled]);
    Payment::factory()->create([
        'ticket_order_id' => $order3->ticket_order_id,
        'amount' => 30.0,
        'status' => PaymentStatus::Completed,
    ]);

    $viewModel = new EventKpiViewModel($event);

    // 100 - 20 + 50 = 130
    expect($viewModel->netRevenue())->toBe(130.0);
});

it('calculates check-in rate correctly over active attendees only', function (): void {
    $event = Event::factory()->create();
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);

    // 2 asistentes activos (uno checkeado y otro no)
    $attendee1 = Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id, 'status' => AttendeeStatus::Active]);
    $attendee2 = Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id, 'status' => AttendeeStatus::Active]);

    // 1 asistente cancelado (checkeado pero no debe contar en el ratio)
    $attendee3 = Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id, 'status' => AttendeeStatus::Cancelled]);

    $checkInList = CheckInList::factory()->create(['event_id' => $event->event_id]);

    ActiveCheckIn::factory()->create([
        'check_in_list_id' => $checkInList->check_in_list_id,
        'attendee_id' => $attendee1->attendee_id,
    ]);
    ActiveCheckIn::factory()->create([
        'check_in_list_id' => $checkInList->check_in_list_id,
        'attendee_id' => $attendee3->attendee_id,
    ]);

    $viewModel = new EventKpiViewModel($event);

    // 1 check-in activo de 2 asistentes activos = 50%
    expect($viewModel->checkInRate())->toBe(50.0);
});

it('shows capacity utilization string based on ticket capacities', function (): void {
    $event = Event::factory()->create();
    $product = Product::factory()->create(['event_id' => $event->event_id]);

    // Tarifas con capacidad total de 10
    ProductPrice::factory()->create(['product_id' => $product->product_id, 'capacity' => 6]);
    ProductPrice::factory()->create(['product_id' => $product->product_id, 'capacity' => 4]);

    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id, 'status' => AttendeeStatus::Active]);

    $viewModel = new EventKpiViewModel($event);

    // 1 vendido de 10 = 10%
    expect($viewModel->capacityUtilization())->toContain('1 / 10 (10%)');
});

it('returns Unlimited for capacity utilization if any product has unlimited price', function (): void {
    $event = Event::factory()->create();
    $product = Product::factory()->create(['event_id' => $event->event_id]);

    // Tarifa ilimitada
    ProductPrice::factory()->create(['product_id' => $product->product_id, 'capacity' => null]);

    $viewModel = new EventKpiViewModel($event);

    expect($viewModel->capacityUtilization())->toBe('Unlimited');
});

it('counts active waitlist requests as Waiting and Notified entries', function (): void {
    $event = Event::factory()->create();

    // 1 waiting, 1 notified, 1 expired
    WaitlistEntry::factory()->create(['event_id' => $event->event_id, 'status' => WaitlistStatus::Waiting]);
    WaitlistEntry::factory()->create(['event_id' => $event->event_id, 'status' => WaitlistStatus::Notified]);
    WaitlistEntry::factory()->create(['event_id' => $event->event_id, 'status' => WaitlistStatus::Expired]);

    $viewModel = new EventKpiViewModel($event);

    expect($viewModel->activeWaitlistCount())->toBe(2);
});
