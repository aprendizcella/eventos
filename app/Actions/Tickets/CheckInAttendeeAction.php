<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AttendeeStatus;
use App\Events\Tickets\AttendeeCheckedIn;
use App\Exceptions\Tickets\CheckInException;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInLog;
use App\Services\Tickets\ValidateQrCodeService;
use Illuminate\Support\Facades\DB;

final readonly class CheckInAttendeeAction
{
    public function __construct(
        private ValidateQrCodeService $validateQrCodeService,
    ) {}

    /**
     * Registra el acceso de un asistente en una lista de check-in.
     *
     * @throws CheckInException
     */
    public function __invoke(string $uniqueCode, int $checkInListId, ?int $operatorUserId = null): ActiveCheckIn
    {
        return DB::transaction(function () use ($uniqueCode, $checkInListId, $operatorUserId) {
            // 1. Obtener y bloquear el asistente (lockForUpdate) para prevenir deadlocks
            /** @var Attendee|null $attendee */
            $attendee = Attendee::query()
                ->where('unique_code', $uniqueCode)
                ->lockForUpdate()
                ->first();

            if ($attendee === null) {
                throw CheckInException::validationFailed('Ticket code does not exist.');
            }

            // 2. Validar con el servicio
            $validation = $this->validateQrCodeService->validate($uniqueCode, $checkInListId);

            if (!$validation->isValid) {
                throw CheckInException::validationFailed($validation->message);
            }

            // 3. Crear el check-in activo
            /** @var ActiveCheckIn $activeCheckIn */
            $activeCheckIn = ActiveCheckIn::query()->create([
                'check_in_list_id' => $checkInListId,
                'attendee_id' => $attendee->attendee_id,
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $operatorUserId,
            ]);

            // 4. Crear log histórico de auditoría
            CheckInLog::query()->create([
                'check_in_list_id' => $checkInListId,
                'attendee_id' => $attendee->attendee_id,
                'action' => 'check_in',
                'user_id' => $operatorUserId,
            ]);

            // 5. Actualizar estado del asistente
            $attendee->status = AttendeeStatus::CheckedIn;
            $attendee->save();

            // 6. Lanzar evento de dominio
            event(new AttendeeCheckedIn($attendee, $activeCheckIn));

            return $activeCheckIn;
        });
    }
}
