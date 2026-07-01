<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PromoCode;

final class PromoCodeValidator
{
    /**
     * Valida si un código promocional es utilizable para un evento en la fecha actual.
     */
    public function isValid(PromoCode $promoCode, int $eventId): bool
    {
        if ($promoCode->event_id !== $eventId || $promoCode->status !== 'active') {
            return false;
        }

        $now = \Illuminate\Support\Facades\Date::now();

        $started = $promoCode->start_at === null || !$now->lessThan($promoCode->start_at);
        $notExpired = $promoCode->end_at === null || !$now->greaterThan($promoCode->end_at);
        $hasUses = $promoCode->max_uses === null || $promoCode->uses_count < $promoCode->max_uses;

        return $started && $notExpired && $hasUses;
    }
}
