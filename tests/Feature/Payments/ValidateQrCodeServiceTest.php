<?php

declare(strict_types=1);

use App\Enums\AttendeeStatus;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use App\Models\Product;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use App\Services\Tickets\ValidateQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('validates non-existent ticket code', function (): void {
    $list = CheckInList::factory()->create();
    $service = new ValidateQrCodeService;

    $result = $service->validate('TKT-NONEXISTENT', $list->check_in_list_id);

    expect($result->isValid)->toBeFalse()
        ->and($result->status)->toBe('invalid_code')
        ->and($result->message)->toContain('does not exist');
});

it('rejects cancelled tickets', function (): void {
    $list = CheckInList::factory()->create();
    $attendee = Attendee::factory()->create([
        'status' => AttendeeStatus::Cancelled,
    ]);

    $service = new ValidateQrCodeService;
    $result = $service->validate($attendee->unique_code, $list->check_in_list_id);

    expect($result->isValid)->toBeFalse()
        ->and($result->status)->toBe('cancelled_ticket')
        ->and($result->message)->toContain('cancelled');
});

it('rejects tickets from another event', function (): void {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    $list = CheckInList::factory()->create(['event_id' => $event1->event_id]);

    $order = TicketOrder::factory()->create(['event_id' => $event2->event_id]);
    $orderItem = TicketOrderItem::factory()->create(['ticket_order_id' => $order->ticket_order_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'ticket_order_item_id' => $orderItem->ticket_order_item_id,
        'status' => AttendeeStatus::Active,
    ]);

    $service = new ValidateQrCodeService;
    $result = $service->validate($attendee->unique_code, $list->check_in_list_id);

    expect($result->isValid)->toBeFalse()
        ->and($result->status)->toBe('wrong_event')
        ->and($result->message)->toContain('another event');
});

it('enforces product eligibility restrictions', function (): void {
    $event = Event::factory()->create();
    $list = CheckInList::factory()->create(['event_id' => $event->event_id]);

    $vipProduct = Product::factory()->create(['event_id' => $event->event_id]);
    $generalProduct = Product::factory()->create(['event_id' => $event->event_id]);

    // Restringir la lista de check-in únicamente al producto VIP
    $list->eligibleProducts()->attach($vipProduct->product_id);

    // 1. Asistente con entrada VIP (debería pasar)
    $order1 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $orderItem1 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'product_id' => $vipProduct->product_id,
    ]);
    $vipAttendee = Attendee::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'ticket_order_item_id' => $orderItem1->ticket_order_item_id,
        'status' => AttendeeStatus::Active,
    ]);

    // 2. Asistente con entrada General (debería fallar)
    $order2 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $orderItem2 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'product_id' => $generalProduct->product_id,
    ]);
    $generalAttendee = Attendee::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'ticket_order_item_id' => $orderItem2->ticket_order_item_id,
        'status' => AttendeeStatus::Active,
    ]);

    $service = new ValidateQrCodeService;

    $resultVip = $service->validate($vipAttendee->unique_code, $list->check_in_list_id);
    $resultGeneral = $service->validate($generalAttendee->unique_code, $list->check_in_list_id);

    expect($resultVip->isValid)->toBeTrue()
        ->and($resultGeneral->isValid)->toBeFalse()
        ->and($resultGeneral->status)->toBe('not_eligible')
        ->and($resultGeneral->message)->toContain('not allowed');
});

it('rejects duplicate check-in at the same list', function (): void {
    $event = Event::factory()->create();
    $list = CheckInList::factory()->create(['event_id' => $event->event_id]);

    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $orderItem = TicketOrderItem::factory()->create(['ticket_order_id' => $order->ticket_order_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'ticket_order_item_id' => $orderItem->ticket_order_item_id,
        'status' => AttendeeStatus::CheckedIn,
    ]);

    // Crear un check-in activo ya registrado
    ActiveCheckIn::factory()->create([
        'check_in_list_id' => $list->check_in_list_id,
        'attendee_id' => $attendee->attendee_id,
        'checked_in_at' => now()->subMinutes(10),
    ]);

    $service = new ValidateQrCodeService;
    $result = $service->validate($attendee->unique_code, $list->check_in_list_id);

    expect($result->isValid)->toBeFalse()
        ->and($result->status)->toBe('duplicate')
        ->and($result->message)->toContain('already been scanned');
});

it('successfully validates a valid attendee ticket', function (): void {
    $event = Event::factory()->create();
    $list = CheckInList::factory()->create(['event_id' => $event->event_id]);

    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $orderItem = TicketOrderItem::factory()->create(['ticket_order_id' => $order->ticket_order_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'ticket_order_item_id' => $orderItem->ticket_order_item_id,
        'status' => AttendeeStatus::Active,
    ]);

    $service = new ValidateQrCodeService;
    $result = $service->validate($attendee->unique_code, $list->check_in_list_id);

    expect($result->isValid)->toBeTrue()
        ->and($result->status)->toBe('success')
        ->and($result->attendee->attendee_id)->toBe($attendee->attendee_id);
});
