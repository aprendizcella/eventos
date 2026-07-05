<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\DataTransferObjects\Notifications\SendBulkMessageDto;
use App\Enums\NotificationLogStatus;
use App\Jobs\Notifications\SendBulkEmailJob;
use App\Models\Attendee;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\DB;

final readonly class SendBulkMessageAction
{
    public function __invoke(SendBulkMessageDto $dto, int $userId): NotificationLog
    {
        return DB::transaction(function () use ($dto, $userId): NotificationLog {
            // 1. Contar destinatarios elegibles usando el Query Scope reutilizable
            $recipientCount = Attendee::query()
                ->forEventSegment($dto->eventId, [
                    'product_price_id' => $dto->productPriceId,
                    'attendee_status' => $dto->attendeeStatus,
                    'check_in_status' => $dto->checkInStatus,
                ])
                ->count();

            // 2. Crear el Log en estado Pending
            /** @var NotificationLog $log */
            $log = NotificationLog::query()->create([
                'event_id' => $dto->eventId,
                'sent_by_user_id' => $userId,
                'subject' => $dto->subject,
                'body' => $dto->body,
                'recipient_count' => $recipientCount,
                'status' => NotificationLogStatus::Pending,
                'filter_criteria' => [
                    'product_price_id' => $dto->productPriceId,
                    'attendee_status' => $dto->attendeeStatus,
                    'check_in_status' => $dto->checkInStatus,
                ],
            ]);

            // 3. Despachar el Job asíncrono con la garantía transaccional afterCommit()
            dispatch(new SendBulkEmailJob($log->notification_log_id))->afterCommit();

            return $log;
        });
    }
}
