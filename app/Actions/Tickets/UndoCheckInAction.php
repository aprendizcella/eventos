<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AttendeeStatus;
use App\Events\Tickets\CheckInUndone;
use App\Exceptions\Tickets\CheckInException;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInLog;
use Illuminate\Support\Facades\DB;

final readonly class UndoCheckInAction
{
    /**
     * Revierte un registro de check-in activo.
     *
     * @throws CheckInException
     */
    public function __invoke(int $attendeeId, int $checkInListId, ?int $operatorUserId = null): void
    {
        DB::transaction(function () use ($attendeeId, $checkInListId, $operatorUserId) {
            // 1. Obtener y bloquear el asistente (lockForUpdate) coincidiendo con el orden de CheckInAttendeeAction
            /** @var Attendee|null $attendee */
            $attendee = Attendee::query()
                ->where('attendee_id', $attendeeId)
                ->lockForUpdate()
                ->first();

            if ($attendee === null) {
                throw CheckInException::notFound();
            }

            // 2. Buscar el check-in activo
            /** @var ActiveCheckIn|null $activeCheckIn */
            $activeCheckIn = ActiveCheckIn::query()
                ->where('check_in_list_id', $checkInListId)
                ->where('attendee_id', $attendeeId)
                ->first();

            if ($activeCheckIn === null) {
                throw CheckInException::activeRecordNotFound();
            }

            // 3. Eliminar físicamente el registro de active_check_in para liberar la clave única
            $activeCheckIn->delete();

            // 4. Registrar en el log histórico de auditoría
            CheckInLog::query()->create([
                'check_in_list_id' => $checkInListId,
                'attendee_id' => $attendeeId,
                'action' => 'undo',
                'user_id' => $operatorUserId,
            ]);

            $ticketOrder = $attendee->ticketOrder;

            if ($ticketOrder === null) {
                throw CheckInException::orderNotFound();
            }

            // 5. Verificar si tiene otros accesos activos en este evento
            $hasOtherCheckIns = ActiveCheckIn::query()
                ->where('attendee_id', $attendeeId)
                ->whereHas('checkInList', function ($query) use ($ticketOrder) {
                    $query->where('event_id', $ticketOrder->event_id);
                })
                ->exists();

            if (!$hasOtherCheckIns) {
                $attendee->status = AttendeeStatus::Active;
                $attendee->save();
            }

            // 6. Lanzar evento de dominio
            event(new CheckInUndone($attendee));
        });
    }
}
