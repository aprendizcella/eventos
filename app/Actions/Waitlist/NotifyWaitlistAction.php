<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Mail\WaitlistNotificationMail;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final readonly class NotifyWaitlistAction
{
    public function __invoke(WaitlistEntry $entry): WaitlistEntry
    {
        return DB::transaction(function () use ($entry): WaitlistEntry {
            // Recargar con bloqueo para evitar carreras
            /** @var WaitlistEntry $entry */
            $entry = WaitlistEntry::query()
                ->where('waitlist_entry_id', $entry->waitlist_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotencia: si ya está notificado o reservado, retornar sin alterar nada
            if (in_array($entry->status, [WaitlistStatus::Notified, WaitlistStatus::Reserved], true)) {
                return $entry;
            }

            // Generar token criptográfico único (32 caracteres hexadecimales)
            $token = bin2hex(random_bytes(16));

            $entry->update([
                'status' => WaitlistStatus::Notified,
                'token' => $token,
                'notified_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            // Encolar correo asíncronamente
            $checkoutUrl = route('checkout', [
                'event' => $entry->event_id,
                'waitlist_token' => $token,
            ]);

            Mail::to($entry->email)->queue(
                new WaitlistNotificationMail($checkoutUrl, $entry->event->title),
            );

            return $entry;
        });
    }
}
