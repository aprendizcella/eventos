<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketOrder;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('allows authorized organizer member to fetch attendees list via API', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id, 'first_name' => 'Luis']);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.events.attendees', $event));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'attendee_id',
                    'first_name',
                    'last_name',
                    'email',
                    'status',
                    'unique_code',
                    'checked_in',
                    'custom_answers',
                ],
            ],
        ])
        ->assertJsonFragment(['first_name' => 'Luis']);
});

it('allows authorized organizer member to register check-in via API', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'unique_code' => 'TKT-API-CHECKIN',
    ]);

    $list = CheckInList::factory()->create(['event_id' => $event->event_id]);

    Sanctum::actingAs($user);

    $response = $this->postJson(route('api.events.check-in', $event), [
        'unique_code' => 'TKT-API-CHECKIN',
        'check_in_list_id' => $list->check_in_list_id,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Check-in registered successfully.']);

    $this->assertDatabaseHas('active_check_in', [
        'check_in_list_id' => $list->check_in_list_id,
        'attendee_id' => $attendee->attendee_id,
    ]);
});

it('allows authorized organizer member to queue bulk email campaign via API', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    Attendee::factory()->create(['ticket_order_id' => $order->ticket_order_id]);

    Sanctum::actingAs($user);

    $response = $this->postJson(route('api.events.messages', $event), [
        'subject' => 'Asunto API',
        'body' => 'Hola {{first_name}}',
    ]);

    $response->assertAccepted()
        ->assertJson(['message' => 'Bulk email campaign queued successfully.']);
});

it('denies access to api endpoints for unauthorized users or other tenants', function (): void {
    $user = User::factory()->create();
    $otherEvent = Event::factory()->create(); // Evento de otro organizador

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.events.attendees', $otherEvent));

    $response->assertForbidden();
});
