<?php

declare(strict_types=1);

namespace Tests\Feature\Attendees;

use App\Actions\Attendees\ExportAttendeesAction;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('exports attendees correctly in csv stream with lazyById and correlated checkin subquery', function (): void {
    $event = Event::factory()->create();
    $product = Product::factory()->create(['event_id' => $event->event_id]);
    $price = ProductPrice::factory()->create(['product_id' => $product->product_id, 'name' => 'General VIP']);

    // Crear asistente 1: Checked In
    $order1 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $item1 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'product_price_id' => $price->product_price_id,
    ]);
    $attendee1 = Attendee::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'ticket_order_item_id' => $item1->ticket_order_item_id,
        'first_name' => 'Luis',
        'last_name' => 'Gómez',
        'email' => 'luis@example.com',
        'unique_code' => 'TKT-11111',
    ]);

    $checkInList = CheckInList::factory()->create(['event_id' => $event->event_id]);
    ActiveCheckIn::factory()->create([
        'check_in_list_id' => $checkInList->check_in_list_id,
        'attendee_id' => $attendee1->attendee_id,
    ]);

    // Crear asistente 2: Not Checked In
    $order2 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $item2 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'product_price_id' => $price->product_price_id,
    ]);
    $attendee2 = Attendee::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'ticket_order_item_id' => $item2->ticket_order_item_id,
        'first_name' => 'Ana',
        'last_name' => 'Sosa',
        'email' => 'ana@example.com',
        'unique_code' => 'TKT-22222',
    ]);

    $action = resolve(ExportAttendeesAction::class);
    $callback = $action($event, []);

    // Capturar el output del stream
    ob_start();
    $callback();
    $csvOutput = ob_get_clean();

    expect($csvOutput)->not->toBeEmpty();

    // Verificar BOM UTF-8
    expect(str_starts_with($csvOutput, chr(0xEF).chr(0xBB).chr(0xBF)))->toBeTrue();

    // Remover BOM para aserciones de texto
    $cleanCsv = substr($csvOutput, 3);

    expect($cleanCsv)->toContain('"Ticket Code","First Name","Last Name",Email,Status,"Ticket Type","Order Reference","Checked In","Custom Answers (JSON)"')
        ->and($cleanCsv)->toContain('TKT-11111,Luis,Gómez,luis@example.com,active,"General VIP"')
        ->and($cleanCsv)->toContain('Yes') // Checked in Yes
        ->and($cleanCsv)->toContain('TKT-22222,Ana,Sosa,ana@example.com,active,"General VIP"')
        ->and($cleanCsv)->toContain('No'); // Checked in No
});

it('applies segment filters to export stream', function (): void {
    $event = Event::factory()->create();
    $product = Product::factory()->create(['event_id' => $event->event_id]);
    $price1 = ProductPrice::factory()->create(['product_id' => $product->product_id, 'name' => 'VIP']);
    $price2 = ProductPrice::factory()->create(['product_id' => $product->product_id, 'name' => 'General']);

    // VIP Attendee
    $order1 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $item1 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'product_price_id' => $price1->product_price_id,
    ]);
    $attendee1 = Attendee::factory()->create([
        'ticket_order_id' => $order1->ticket_order_id,
        'ticket_order_item_id' => $item1->ticket_order_item_id,
        'unique_code' => 'TKT-VIP',
    ]);

    // General Attendee
    $order2 = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $item2 = TicketOrderItem::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'product_price_id' => $price2->product_price_id,
    ]);
    $attendee2 = Attendee::factory()->create([
        'ticket_order_id' => $order2->ticket_order_id,
        'ticket_order_item_id' => $item2->ticket_order_item_id,
        'unique_code' => 'TKT-GEN',
    ]);

    $action = resolve(ExportAttendeesAction::class);

    // Filtrar solo por VIP
    $callback = $action($event, ['product_price_id' => $price1->product_price_id]);

    ob_start();
    $callback();
    $csvOutput = ob_get_clean();

    expect($csvOutput)->toContain('TKT-VIP')
        ->and($csvOutput)->not->toContain('TKT-GEN');
});
