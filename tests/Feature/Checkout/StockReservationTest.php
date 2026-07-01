<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\DataTransferObjects\Orders\ReserveStockDto;
use App\DataTransferObjects\Orders\ReserveStockItemDto;
use App\Enums\TicketOrderStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\TicketOrder;
use App\Services\StockManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('calculates available stock correctly factoring in active reservations', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
    ]);
    /** @var ProductPrice $price */
    $price = $product->prices()->create([
        'name' => 'General Price',
        'price' => 50.00,
        'capacity' => 10,
        'quantity_sold' => 2,
    ]);

    /** @var StockManager $stockManager */
    $stockManager = resolve(StockManager::class);

    // Initial available capacity: 10 - 2 = 8
    expect($stockManager->getAvailableCapacity($price))->toBe(8);

    // Create a reserved order for 3 tickets
    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'reserved_until' => now()->addMinutes(10),
    ]);
    $order->items()->create([
        'product_id' => $product->product_id,
        'product_price_id' => $price->product_price_id,
        'quantity' => 3,
        'price' => 50.00,
        'subtotal' => 150.00,
        'total' => 150.00,
    ]);

    // Available capacity should now be: 10 - 2 (sold) - 3 (reserved) = 5
    expect($stockManager->getAvailableCapacity($price))->toBe(5);

    // If reservation expires, it shouldn't count towards active reservations
    $expiredOrder = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'reserved_until' => now()->subMinutes(1), // already expired
    ]);
    $expiredOrder->items()->create([
        'product_id' => $product->product_id,
        'product_price_id' => $price->product_price_id,
        'quantity' => 2,
        'price' => 50.00,
        'subtotal' => 100.00,
        'total' => 100.00,
    ]);

    // Should still be 5, since the second order is expired
    expect($stockManager->getAvailableCapacity($price))->toBe(5);
});

it('throws exception if reservation quantity exceeds available stock', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
    ]);
    $price = $product->prices()->create([
        'name' => 'VIP Price',
        'price' => 200.00,
        'capacity' => 2,
        'quantity_sold' => 0,
    ]);

    /** @var StockManager $stockManager */
    $stockManager = resolve(StockManager::class);

    $dto = new ReserveStockDto(
        firstName: 'Alice',
        lastName: 'Smith',
        email: 'alice@example.com',
        promoCodeId: null,
        items: [
            new ReserveStockItemDto($price->product_price_id, 3), // Attempting 3, but capacity is 2
        ],
    );

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage(__('Not enough tickets available for: :name', ['name' => $price->name]));

    $stockManager->reserve($event, $dto);
});

it('releases expired reservations when scheduler command runs', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    // Create an expired reservation
    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'reserved_until' => now()->subMinutes(5), // expired 5 minutes ago
    ]);

    // Run release expired command
    Artisan::call('app:release-expired-reservations');

    $order->refresh();
    expect($order->status)->toBe(TicketOrderStatus::Expired)
        ->and($order->reserved_until)->toBeNull();
});
