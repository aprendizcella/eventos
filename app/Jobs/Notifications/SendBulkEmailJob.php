<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Enums\NotificationLogStatus;
use App\Mail\BulkEventMessageMail;
use App\Models\Attendee;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use stdClass;
use Throwable;

/**
 * This job receives the notification log ID in its constructor and queries by it directly.
 * It does NOT need tenant context — it processes the exact notification it was given.
 */
final class SendBulkEmailJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public int $timeout = 600;

    public function __construct(public int $notificationLogId) {}

    public function handle(): void
    {
        /** @var NotificationLog|null $log */
        $log = NotificationLog::query()->find($this->notificationLogId);

        if ($log === null || $log->status !== NotificationLogStatus::Pending) {
            return;
        }

        $log->update(['status' => NotificationLogStatus::Processing]);

        try {
            $filters = $log->filter_criteria ?? [];

            // Query filtrando destinatarios excluyendo los que ya están encolados ('queued')
            $query = Attendee::query()
                ->select('attendee.*')
                ->forEventSegment($log->event_id, $filters)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('notification_recipient_log')
                        ->where('notification_recipient_log.notification_log_id', $this->notificationLogId)
                        ->where('notification_recipient_log.status', 'queued')
                        ->whereColumn('notification_recipient_log.attendee_id', 'attendee.attendee_id');
                });

            // Procesar secuencialmente con chunkById usando attendee_id sin calificador de tabla
            $query->chunkById(100, function ($attendees) use ($log) {
                foreach ($attendees as $attendee) {
                    // Estrategia Claim-or-Reuse atómica con bloqueo de base de datos
                    /** @var stdClass|null $recipientLog */
                    $recipientLog = DB::transaction(function () use ($log, $attendee) {
                        // 1. Intentar insertar el registro Outbox como pending.
                        // Ignorará de forma segura si ya existía de un intento asíncrono fallido anterior.
                        DB::table('notification_recipient_log')->insertOrIgnore([
                            'notification_log_id' => $log->notification_log_id,
                            'attendee_id' => $attendee->attendee_id,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // 2. Recuperar con bloqueo para evitar colisiones en entornos altamente concurrentes
                        return DB::table('notification_recipient_log')
                            ->where('notification_log_id', $log->notification_log_id)
                            ->where('attendee_id', $attendee->attendee_id)
                            ->lockForUpdate()
                            ->first();
                    });

                    // Si ya está encolado en otro intento/hilo anterior, omitir
                    if ($recipientLog === null || $recipientLog->status === 'queued') {
                        continue;
                    }

                    try {
                        assert($log->event instanceof \App\Models\Event);
                        $parsedBody = $this->parsePlaceholders($log->body, $attendee, $log->event->title);

                        // 3. Encolar email individual en Redis/BD
                        Mail::to($attendee->email)->queue(
                            new BulkEventMessageMail($log->subject, $parsedBody),
                        );

                        // 4. Confirmación exitosa en el outbox
                        DB::table('notification_recipient_log')
                            ->where('notification_recipient_log_id', $recipientLog->notification_recipient_log_id)
                            ->update([
                                'status' => 'queued',
                                'updated_at' => now(),
                            ]);

                    } catch (Throwable $mailException) {
                        // Registrar fallo para permitir reintentos específicos sobre este destinatario
                        DB::table('notification_recipient_log')
                            ->where('notification_recipient_log_id', $recipientLog->notification_recipient_log_id)
                            ->update([
                                'status' => 'failed',
                                'updated_at' => now(),
                            ]);

                        throw $mailException; // Gatillar reintento del Job de cola
                    }
                }
            }, 'attendee_id');

            // Log completado con marca de finalización de encolado
            $log->update([
                'status' => NotificationLogStatus::Completed,
                'completed_at' => now(),
            ]);

        } catch (Throwable $e) {
            $log->update(['status' => NotificationLogStatus::Failed]);

            throw $e;
        }
    }

    private function parsePlaceholders(string $body, Attendee $attendee, string $eventTitle): string
    {
        $replace = [
            '{{first_name}}' => $attendee->first_name,
            '{{last_name}}' => $attendee->last_name,
            '{{event_title}}' => $eventTitle,
            '{{ticket_code}}' => $attendee->unique_code,
        ];

        return str_replace(array_keys($replace), array_values($replace), $body);
    }
}
