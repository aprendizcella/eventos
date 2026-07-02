<?php

declare(strict_types=1);

use App\Actions\Tickets\CheckInAttendeeAction;
use App\Actions\Tickets\UndoCheckInAction;
use App\Enums\AttendeeStatus;
use App\Events\Tickets\AttendeeCheckedIn;
use App\Events\Tickets\CheckInUndone;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\CheckInLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('performs check-in action successfully, sets status and logs activity', function (): void {
    Event::fake([AttendeeCheckedIn::class]);

    $list = CheckInList::factory()->create();
    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create([
        'status' => AttendeeStatus::Active,
        'ticket_order_id' => App\Models\TicketOrder::factory()->create([
            'event_id' => $list->event_id,
        ])->ticket_order_id,
    ]);

    $operator = User::factory()->create();

    $action = resolve(CheckInAttendeeAction::class);
    $activeCheckIn = $action($attendee->unique_code, $list->check_in_list_id, $operator->id);

    expect($activeCheckIn)->toBeInstanceOf(ActiveCheckIn::class)
        ->and($activeCheckIn->attendee_id)->toBe($attendee->attendee_id)
        ->and($activeCheckIn->check_in_list_id)->toBe($list->check_in_list_id);

    // Estado del asistente debe actualizarse a checked_in
    $attendee->refresh();
    expect($attendee->status)->toBe(AttendeeStatus::CheckedIn);

    // Debe crearse un log de auditoría
    $log = CheckInLog::query()->first();
    expect($log)->not->toBeNull()
        ->and($log->attendee_id)->toBe($attendee->attendee_id)
        ->and($log->action)->toBe('check_in')
        ->and($log->user_id)->toBe($operator->id);

    // Evento debe dispararse
    Event::assertDispatched(AttendeeCheckedIn::class, fn ($event) => $event->attendee->attendee_id === $attendee->attendee_id
        && $event->activeCheckIn->active_check_in_id === $activeCheckIn->active_check_in_id);
});

it('prevents check-in if validation fails', function (): void {
    $list = CheckInList::factory()->create();
    $attendee = Attendee::factory()->create([
        'status' => AttendeeStatus::Cancelled,
    ]);

    $action = resolve(CheckInAttendeeAction::class);

    expect(fn () => $action($attendee->unique_code, $list->check_in_list_id))
        ->toThrow(App\Exceptions\Tickets\CheckInException::class);
});

it('reverts check-in via undo action, deletes active row, and appends undo log', function (): void {
    Event::fake([CheckInUndone::class]);

    $list = CheckInList::factory()->create();
    /** @var Attendee $attendee */
    $attendee = Attendee::factory()->create([
        'status' => AttendeeStatus::CheckedIn,
        'ticket_order_id' => App\Models\TicketOrder::factory()->create([
            'event_id' => $list->event_id,
        ])->ticket_order_id,
    ]);

    // Crear check-in activo previo
    ActiveCheckIn::factory()->create([
        'check_in_list_id' => $list->check_in_list_id,
        'attendee_id' => $attendee->attendee_id,
    ]);

    $operator = User::factory()->create();

    $undoAction = resolve(UndoCheckInAction::class);
    $undoAction($attendee->attendee_id, $list->check_in_list_id, $operator->id);

    // active_check_in debe eliminarse físicamente para permitir re-entrada
    expect(ActiveCheckIn::query()->count())->toBe(0);

    // El asistente debe volver a active si no tiene otros check-ins
    $attendee->refresh();
    expect($attendee->status)->toBe(AttendeeStatus::Active);

    // Debe existir un log de tipo undo
    $log = CheckInLog::query()->where('action', 'undo')->first();
    expect($log)->not->toBeNull()
        ->and($log->attendee_id)->toBe($attendee->attendee_id)
        ->and($log->user_id)->toBe($operator->id);

    // Evento debe dispararse
    Event::assertDispatched(CheckInUndone::class, fn ($event) => $event->attendee->attendee_id === $attendee->attendee_id);
});
