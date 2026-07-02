<?php

declare(strict_types=1);

use App\Models\Attendee;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates an attendee successfully and implements soft deletes', function (): void {
    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create();

    expect($attendee)->toBeInstanceOf(Attendee::class)
        ->and($attendee->deleted_at)->toBeNull();

    $attendee->delete();

    expect($attendee->trashed())->toBeTrue();
});

it('enforces composite unique constraint on ticket_order_item_id and sequence', function (): void {
    $orderItem = TicketOrderItem::factory()->create();

    Attendee::factory()->create([
        'ticket_order_item_id' => $orderItem->ticket_order_item_id,
        'sequence' => 1,
    ]);

    // Intentar crear otro asistente con el mismo item y la misma secuencia debe fallar
    expect(fn () => Attendee::factory()->create([
        'ticket_order_item_id' => $orderItem->ticket_order_item_id,
        'sequence' => 1,
    ]))->toThrow(QueryException::class);
});

it('restricts ticket order deletion if active attendees exist', function (): void {
    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create();

    // Intentar borrar la orden directamente debe arrojar una QueryException por la restricción de FK
    expect(fn () => TicketOrder::query()->where('ticket_order_id', $attendee->ticket_order_id)->delete())
        ->toThrow(QueryException::class);
});
