<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\AttendeeStatus;
use App\Enums\TicketOrderStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\TicketOrder;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('renders the event dashboard tab with KPI cards and SVG sales chart', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    // Audiencia controlada: 1 asistente activo
    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Completed,
    ]);
    Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'status' => AttendeeStatus::Active,
    ]);
    Payment::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'amount' => 120.50,
    ]);

    $this->actingAs($user);

    Volt::test('organizers.events.event-dashboard', ['event' => $event])
        ->assertSee(__('Net Revenue'))
        ->assertSee(__('Tickets Sold'))
        ->assertSee(__('Check-In Rate'))
        ->assertSee(__('Waitlist Requests'))
        ->assertSee(__('Daily Net Revenue (Last 30 Days)'))
        ->assertSee(__('Capacity Status'))
        ->assertSee(__('Capacity utilization'))
        ->assertSee('rounded-xl border border-gray-200 bg-white', false)
        ->assertSee('<svg', false)
        ->assertSee('polyline', false);
});

it('shows unlimited capacity when product prices have null capacity', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->actingAs($user);

    Volt::test('organizers.events.event-dashboard', ['event' => $event])
        ->assertSee(__('Unlimited'));
});
