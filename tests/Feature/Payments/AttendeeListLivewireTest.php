<?php

declare(strict_types=1);

use App\Enums\AttendeeStatus;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketOrder;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->organizer = Organizer::factory()->create();
    $this->event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

    $this->admin = User::factory()->create();
    $this->organizer->users()->attach($this->admin->id, ['role' => OrganizerRoles::Admin->value]);

    $this->list = CheckInList::factory()->create(['event_id' => $this->event->event_id]);
});

it('renders the attendee list component, searches and filters properly', function (): void {
    $this->actingAs($this->admin);

    /** @var Attendee $attendee1 */
    $attendee1 = Attendee::factory()->create([
        'first_name' => 'Albus',
        'last_name' => 'Dumbledore',
        'ticket_order_id' => TicketOrder::factory()->create(['event_id' => $this->event->event_id])->ticket_order_id,
        'status' => AttendeeStatus::Active,
    ]);

    /** @var Attendee $attendee2 */
    $attendee2 = Attendee::factory()->create([
        'first_name' => 'Severus',
        'last_name' => 'Snape',
        'ticket_order_id' => TicketOrder::factory()->create(['event_id' => $this->event->event_id])->ticket_order_id,
        'status' => AttendeeStatus::Active,
    ]);

    Volt::test('organizers.events.attendee-list', ['event' => $this->event])
        ->assertSee('Albus')
        ->assertSee('Severus')
        // Filtrar por búsqueda
        ->set('search', 'Albus')
        ->assertSee('Albus')
        ->assertDontSee('Severus');
});

it('allows organizer admin to perform manual check-in and manual undo', function (): void {
    $this->actingAs($this->admin);

    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => TicketOrder::factory()->create(['event_id' => $this->event->event_id])->ticket_order_id,
        'status' => AttendeeStatus::Active,
    ]);

    // 1. Simular Check-in manual
    Volt::test('organizers.events.attendee-list', ['event' => $this->event])
        ->call('manualCheckIn', $attendee->unique_code)
        ->assertHasNoErrors();

    // El estado en base de datos debe reflejar CheckedIn
    $attendee->refresh();
    expect($attendee->status)->toBe(AttendeeStatus::CheckedIn);
    expect(ActiveCheckIn::query()->where('attendee_id', $attendee->attendee_id)->exists())->toBeTrue();

    // 2. Simular Undo manual
    Volt::test('organizers.events.attendee-list', ['event' => $this->event])
        ->call('manualUndo', $attendee->attendee_id)
        ->assertHasNoErrors();

    // El estado debe volver a Active y borrarse de active_check_in
    $attendee->refresh();
    expect($attendee->status)->toBe(AttendeeStatus::Active);
    expect(ActiveCheckIn::query()->where('attendee_id', $attendee->attendee_id)->exists())->toBeFalse();

    // Debe existir registro histórico en check_in_log
    expect(CheckInLog::query()->where('attendee_id', $attendee->attendee_id)->count())->toBe(2);
});

it('denies check-in actions to viewer role', function (): void {
    $viewer = User::factory()->create();
    $this->organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);
    $this->actingAs($viewer);

    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => TicketOrder::factory()->create(['event_id' => $this->event->event_id])->ticket_order_id,
        'status' => AttendeeStatus::Active,
    ]);

    Volt::test('organizers.events.attendee-list', ['event' => $this->event])
        ->call('manualCheckIn', $attendee->unique_code)
        ->assertStatus(403);
});
