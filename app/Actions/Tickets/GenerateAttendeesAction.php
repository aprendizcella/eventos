<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\AttendeeStatus;
use App\Enums\ProductType;
use App\Exceptions\Tickets\TicketGenerationException;
use App\Models\Attendee;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class GenerateAttendeesAction
{
    /**
     * Genera e inserta los asistentes para un pedido de forma idempotente y segura.
     *
     * @return Collection<int, Attendee>
     *
     * @throws TicketGenerationException
     */
    public function __invoke(TicketOrder $order): Collection
    {
        $order->loadMissing('items.product');
        $attendees = collect();

        foreach ($order->items as $item) {
            if ($item->product === null || $item->product->type !== ProductType::Ticket) {
                continue;
            }

            $this->generateAttendeesForItem($order, $item, $attendees);
        }

        return $attendees;
    }

    /**
     * Genera asistentes para un ítem específico del pedido.
     *
     * @param  Collection<int, Attendee>  $attendees
     *
     * @throws TicketGenerationException
     */
    private function generateAttendeesForItem(TicketOrder $order, TicketOrderItem $item, Collection $attendees): void
    {
        for ($seq = 1; $seq <= $item->quantity; $seq++) {
            $attendees->push($this->findOrCreateAttendee($order, $item, $seq));
        }
    }

    /**
     * Busca un asistente existente para mantener la idempotencia o crea uno nuevo con código único.
     *
     * @throws TicketGenerationException
     */
    private function findOrCreateAttendee(TicketOrder $order, TicketOrderItem $item, int $seq): Attendee
    {
        /** @var Attendee|null $existingAttendee */
        $existingAttendee = Attendee::query()
            ->where('ticket_order_item_id', $item->ticket_order_item_id)
            ->where('sequence', $seq)
            ->first();

        if ($existingAttendee !== null) {
            return $existingAttendee;
        }

        $uniqueCode = $this->generateUniqueCode();

        /** @var Attendee */
        return Attendee::query()->create([
            'ticket_order_id' => $order->ticket_order_id,
            'ticket_order_item_id' => $item->ticket_order_item_id,
            'sequence' => $seq,
            'unique_code' => $uniqueCode,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'email' => $order->email,
            'status' => AttendeeStatus::Active,
        ]);
    }

    /**
     * Genera un código único controlando colisiones.
     *
     * @throws TicketGenerationException
     */
    private function generateUniqueCode(): string
    {
        $maxTries = 5;

        for ($attempt = 1; $attempt <= $maxTries; $attempt++) {
            $candidateCode = 'TKT-'.strtoupper(Str::random(10));
            $exists = Attendee::query()->where('unique_code', $candidateCode)->exists();

            if (!$exists) {
                return $candidateCode;
            }
        }

        throw TicketGenerationException::collisionLimitExceeded($maxTries);
    }
}
