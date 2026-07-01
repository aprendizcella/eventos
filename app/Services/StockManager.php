<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Orders\ReserveStockDto;
use App\Enums\TicketOrderStatus;
use App\Models\Event;
use App\Models\ProductPrice;
use App\Models\PromoCode;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class StockManager
{
    public function __construct(
        private PriceCalculator $priceCalculator,
        private PromoCodeValidator $promoCodeValidator,
    ) {}

    /**
     * Calcula la capacidad disponible para un tier de precio de entrada.
     */
    public function getAvailableCapacity(ProductPrice $price): int
    {
        if ($price->capacity === null) {
            return 999999; // Capacidad ilimitada
        }

        // Sumar cantidades vendidas consolidadas y las reservadas temporalmente
        $activeReservations = (int) DB::table('ticket_order_item')
            ->join('ticket_order', 'ticket_order_item.ticket_order_id', '=', 'ticket_order.ticket_order_id')
            ->where('ticket_order_item.product_price_id', $price->product_price_id)
            ->where('ticket_order.status', TicketOrderStatus::Reserved->value)
            ->where('ticket_order.reserved_until', '>', now())
            ->sum('ticket_order_item.quantity');

        $available = $price->capacity - ($price->quantity_sold + $activeReservations);

        return max(0, $available);
    }

    /**
     * Realiza una reserva atómica de stock y crea el pedido en estado 'reserved'.
     */
    public function reserve(Event $event, ReserveStockDto $dto): TicketOrder
    {
        return DB::transaction(function () use ($event, $dto): TicketOrder {
            $promoCode = $this->resolvePromoCode($event, $dto->promoCodeId);

            [$orderItemsData, $subtotalSum, $discountSum, $totalSum] = $this->processItems($event, $dto, $promoCode);

            // Generar referencia única
            do {
                $reference = 'ORD-'.Str::upper(Str::random(8));
            } while (TicketOrder::query()->where('order_reference', $reference)->exists());

            // Crear el TicketOrder
            /** @var TicketOrder $order */
            $order = TicketOrder::query()->create([
                'event_id' => $event->event_id,
                'promo_code_id' => $promoCode?->promo_code_id,
                'order_reference' => $reference,
                'status' => TicketOrderStatus::Reserved,
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'subtotal' => $subtotalSum,
                'discount' => $discountSum,
                'total' => $totalSum,
                'reserved_until' => now()->addMinutes(10),
            ]);

            // Crear los items
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            activity()
                ->performedOn($order)
                ->useLog('ticket_order')
                ->log('reserved');

            return $order->load('items.productPrice');
        });
    }

    private function resolvePromoCode(Event $event, ?int $promoCodeId): ?PromoCode
    {
        if ($promoCodeId === null) {
            return null;
        }

        /** @var PromoCode|null $foundCode */
        $foundCode = PromoCode::query()->find($promoCodeId);

        if ($foundCode !== null && $this->promoCodeValidator->isValid($foundCode, $event->event_id)) {
            return $foundCode;
        }

        return null;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: float, 2: float, 3: float}
     */
    private function processItems(Event $event, ReserveStockDto $dto, ?PromoCode $promoCode): array
    {
        $priceIds = array_map(fn ($item) => $item->productPriceId, $dto->items);
        $prices = ProductPrice::query()
            ->whereIn('product_price_id', $priceIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('product_price_id');

        $orderItemsData = [];
        $subtotal = 0.0;
        $discount = 0.0;
        $total = 0.0;

        foreach ($dto->items as $itemDto) {
            /** @var ProductPrice|null $price */
            $price = $prices->get($itemDto->productPriceId);

            if ($price === null || $price->product === null || $price->product->event_id !== $event->event_id) {
                throw \App\Exceptions\Orders\OrderException::invalidSelection(__('Invalid ticket selection.'));
            }

            // Verificar stock disponible
            $available = $this->getAvailableCapacity($price);

            if ($itemDto->quantity > $available) {
                throw \App\Exceptions\Orders\OrderException::stockDepleted(__('Not enough tickets available for: :name', ['name' => $price->name]));
            }

            // Calcular precios del item
            $calc = $this->priceCalculator->calculate((float) $price->price, $itemDto->quantity, $promoCode);

            $subtotal += $calc['subtotal'];
            $discount += $calc['discount'];
            $total += $calc['total'];

            $orderItemsData[] = [
                'product_id' => $price->product_id,
                'product_price_id' => $price->product_price_id,
                'quantity' => $itemDto->quantity,
                'price' => (float) $price->price,
                'subtotal' => $calc['subtotal'],
                'discount' => $calc['discount'],
                'total' => $calc['total'],
            ];
        }

        return [$orderItemsData, $subtotal, $discount, $total];
    }
}
