<?php

declare(strict_types=1);

use App\Actions\Tickets\GenerateAttendeesAction;
use App\Enums\AttendeeStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('generates attendees only for ticket products and ensures idempotency', function (): void {
    $order = TicketOrder::factory()->create();

    $productTicket = Product::factory()->create(['type' => ProductType::Ticket]);
    $productMerch = Product::factory()->create(['type' => ProductType::Merchandise]);

    $itemTicket = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'product_id' => $productTicket->product_id,
        'quantity' => 2,
    ]);

    $itemMerch = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'product_id' => $productMerch->product_id,
        'quantity' => 1,
    ]);

    $action = resolve(GenerateAttendeesAction::class);

    // 1. Ejecutar por primera vez: debe crear exactamente 2 asistentes activos
    $attendees = $action($order);

    expect($attendees)->toHaveCount(2);
    $this->assertDatabaseCount('attendee', 2);

    foreach ($attendees as $attendee) {
        expect($attendee->status)->toBe(AttendeeStatus::Active)
            ->and($attendee->ticket_order_id)->toBe($order->ticket_order_id)
            ->and($attendee->ticket_order_item_id)->toBe($itemTicket->ticket_order_item_id)
            ->and($attendee->unique_code)->toStartWith('TKT-');
    }

    // 2. Ejecutar de nuevo: no debe crear nuevos registros (idempotencia)
    $secondAttendees = $action($order);

    expect($secondAttendees)->toHaveCount(2);
    $this->assertDatabaseCount('attendee', 2);
    expect($secondAttendees->pluck('attendee_id')->toArray())->toEqual($attendees->pluck('attendee_id')->toArray());
});
