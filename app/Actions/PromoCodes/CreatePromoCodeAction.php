<?php

declare(strict_types=1);

namespace App\Actions\PromoCodes;

use App\DataTransferObjects\PromoCodes\CreatePromoCodeDto;
use App\Models\Event;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreatePromoCodeAction
{
    public function __invoke(Event $event, CreatePromoCodeDto $dto, User $creator): PromoCode
    {
        return DB::transaction(function () use ($event, $dto, $creator): PromoCode {
            /** @var PromoCode $promoCode */
            $promoCode = PromoCode::query()->create([
                'event_id' => $event->event_id,
                'code' => $dto->code,
                'type' => $dto->type,
                'value' => $dto->value,
                'max_uses' => $dto->max_uses,
                'uses_count' => 0,
                'start_at' => $dto->start_at,
                'end_at' => $dto->end_at,
                'status' => $dto->status,
            ]);

            activity()
                ->performedOn($promoCode)
                ->causedBy($creator)
                ->useLog('promo_code')
                ->log('created');

            return $promoCode;
        });
    }
}
