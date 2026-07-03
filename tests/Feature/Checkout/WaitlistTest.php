<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Actions\Tickets\GenerateAttendeesAction;
use App\Actions\Waitlist\JoinWaitlistAction;
use App\Actions\Waitlist\NotifyWaitlistAction;
use App\DataTransferObjects\Orders\ReserveStockDto;
use App\DataTransferObjects\Orders\ReserveStockItemDto;
use App\Enums\TicketOrderStatus;
use App\Enums\WaitlistStatus;
use App\Exceptions\Waitlist\WaitlistException;
use App\Mail\WaitlistNotificationMail;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\TicketOrder;
use App\Models\WaitlistEntry;
use App\Services\StockManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('normalizes email and prevents duplicate active waitlist registrations', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 10,
    ]);

    $action = resolve(JoinWaitlistAction::class);

    // Primer alta
    $entry = $action($event->event_id, $price->product_price_id, '  TEST@example.com  ', 'John', 'Doe');

    expect($entry->email)->toBe('test@example.com')
        ->and($entry->status)->toBe(WaitlistStatus::Waiting);

    // Intentar duplicado con distinta capitalización y espacios
    expect(fn () => $action($event->event_id, $price->product_price_id, 'test@example.com', 'John', 'Doe'))
        ->toThrow(WaitlistException::class);
});

it('notifies waitlist entry, generates token and sends queued email', function (): void {
    Mail::fake();

    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 10,
    ]);

    $entry = WaitlistEntry::query()->create([
        'event_id' => $event->event_id,
        'product_price_id' => $price->product_price_id,
        'email' => 'waiting@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'status' => WaitlistStatus::Waiting,
    ]);

    $action = resolve(NotifyWaitlistAction::class);
    $notified = $action($entry);

    expect($notified->status)->toBe(WaitlistStatus::Notified)
        ->and($notified->token)->toHaveLength(32)
        ->and($notified->expires_at)->toBeGreaterThan(now()->addHours(23));

    Mail::assertQueued(WaitlistNotificationMail::class, fn ($mail) => $mail->hasTo($notified->email) && str_contains((string) $mail->url, $notified->token));
});

it('excludes active waitlist allocations from stock checks unless own token is provided', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 1, // Capacidad muy limitada
    ]);

    // Crear una entrada en lista de espera notificada (activa)
    $entry = WaitlistEntry::query()->create([
        'event_id' => $event->event_id,
        'product_price_id' => $price->product_price_id,
        'email' => 'waiting@example.com',
        'status' => WaitlistStatus::Notified,
        'token' => 'mockedtoken12345678901234567890',
        'expires_at' => now()->addHours(12),
    ]);

    $stockManager = resolve(StockManager::class);

    // Sin token, la capacidad disponible debe ser 0 (ya reservada para la waitlist)
    expect($stockManager->getAvailableCapacity($price))->toBe(0);

    // Con el token de otro usuario, sigue siendo 0
    expect($stockManager->getAvailableCapacity($price, 'othertoken'))->toBe(0);

    // Con su propio token, la capacidad disponible es 1 (puede comprar su reserva)
    expect($stockManager->getAvailableCapacity($price, $entry->token))->toBe(1);
});

it('validates waitlist token, transits to reserved and links it to order', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 1,
    ]);

    $entry = WaitlistEntry::query()->create([
        'event_id' => $event->event_id,
        'product_price_id' => $price->product_price_id,
        'email' => 'waiting@example.com',
        'status' => WaitlistStatus::Notified,
        'token' => 'mockedtoken12345678901234567890',
        'expires_at' => now()->addHours(12),
    ]);

    $stockManager = resolve(StockManager::class);

    $dto = new ReserveStockDto(
        firstName: 'John',
        lastName: 'Doe',
        email: 'waiting@example.com',
        promoCodeId: null,
        items: [
            new ReserveStockItemDto($price->product_price_id, 1),
        ],
        waitlistToken: $entry->token,
    );

    $order = $stockManager->reserve($event, $dto);

    expect($order->waitlist_entry_id)->toBe($entry->waitlist_entry_id);

    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Reserved);
});

it('rolls back reserved waitlist entry to notified when order cancels or expires', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 1,
    ]);

    $entry = WaitlistEntry::query()->create([
        'event_id' => $event->event_id,
        'product_price_id' => $price->product_price_id,
        'email' => 'waiting@example.com',
        'status' => WaitlistStatus::Reserved, // Reservado
        'token' => 'mockedtoken12345678901234567890',
        'expires_at' => now()->addHours(12), // Vigente
    ]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'waitlist_entry_id' => $entry->waitlist_entry_id,
        'reserved_until' => now()->subMinutes(5),
    ]);

    // Ejecutar expiración
    Artisan::call('app:release-expired-reservations');

    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Notified);
});

it('converts waitlist entry when order is confirmed', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 1,
    ]);

    $entry = WaitlistEntry::query()->create([
        'event_id' => $event->event_id,
        'product_price_id' => $price->product_price_id,
        'email' => 'waiting@example.com',
        'status' => WaitlistStatus::Reserved,
        'token' => 'mockedtoken12345678901234567890',
        'expires_at' => now()->addHours(12),
    ]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'waitlist_entry_id' => $entry->waitlist_entry_id,
        'reserved_until' => now()->addMinutes(10),
    ]);

    $action = resolve(\App\Actions\Orders\ConfirmTicketOrderAction::class);
    $action($order);

    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Converted);
});

it('maps attendee details and custom questions staging correctly', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id, 'organizer_id' => $organizer->id]);
    $price = $product->prices()->create([
        'name' => 'General',
        'price' => 50.00,
        'capacity' => 10,
    ]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Completed,
        'first_name' => 'BuyerFirst',
        'last_name' => 'BuyerLast',
        'email' => 'buyer@example.com',
    ]);

    // Crear un item de orden con respuestas en staging por secuencia
    $item = $order->items()->create([
        'product_id' => $product->product_id,
        'product_price_id' => $price->product_price_id,
        'quantity' => 2,
        'price' => 50.00,
        'subtotal' => 100.00,
        'total' => 100.00,
        'custom_answers_staging' => [
            1 => [
                'first_name' => 'AttendeeOneFirst',
                'last_name' => 'AttendeeOneLast',
                'email' => 'one@example.com',
                'answers' => [
                    'q_1' => 'Answer One',
                ],
            ],
            2 => [
                'first_name' => 'AttendeeTwoFirst',
                'last_name' => 'AttendeeTwoLast',
                'email' => 'two@example.com',
                'answers' => [
                    'q_1' => 'Answer Two',
                ],
            ],
        ],
    ]);

    $action = resolve(GenerateAttendeesAction::class);
    $attendees = $action($order);

    expect($attendees)->toHaveCount(2);

    $first = $attendees->firstWhere('sequence', 1);
    expect($first->first_name)->toBe('AttendeeOneFirst')
        ->and($first->custom_answers)->toBe(['q_1' => 'Answer One']);

    $second = $attendees->firstWhere('sequence', 2);
    expect($second->first_name)->toBe('AttendeeTwoFirst')
        ->and($second->custom_answers)->toBe(['q_1' => 'Answer Two']);
});
