<?php

declare(strict_types=1);

namespace App\Actions\Waitlist;

use App\Enums\WaitlistStatus;
use App\Exceptions\Waitlist\WaitlistException;
use App\Models\WaitlistEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class JoinWaitlistAction
{
    public function __invoke(int $eventId, int $productPriceId, string $email, ?string $firstName = null, ?string $lastName = null): WaitlistEntry
    {
        $normalizedEmail = strtolower(trim($email));

        return DB::transaction(function () use ($eventId, $productPriceId, $normalizedEmail, $firstName, $lastName): WaitlistEntry {
            // Validación server-side lógica en PHP (prevención de duplicados activos antes de insertar)
            $exists = WaitlistEntry::query()
                ->where('product_price_id', $productPriceId)
                ->where('email', $normalizedEmail)
                ->whereIn('status', [WaitlistStatus::Waiting, WaitlistStatus::Notified, WaitlistStatus::Reserved])
                ->exists();

            if ($exists) {
                throw WaitlistException::alreadyRegistered(__('You are already registered on the active waitlist for this ticket tier.'));
            }

            try {
                /** @var WaitlistEntry */
                return WaitlistEntry::query()->create([
                    'event_id' => $eventId,
                    'product_price_id' => $productPriceId,
                    'email' => $normalizedEmail,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'status' => WaitlistStatus::Waiting,
                ]);
            } catch (QueryException $e) {
                // Capturar colisión del índice único active_email_unique en base de datos
                if ($e->getCode() === '23000') {
                    throw WaitlistException::alreadyRegistered(__('You are already registered on the active waitlist for this ticket tier.'));
                }

                throw $e;
            }
        });
    }
}
