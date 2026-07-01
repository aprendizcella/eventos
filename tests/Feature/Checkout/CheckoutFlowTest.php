<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\ProductStatus;
use App\Enums\ProductVisibility;
use App\Enums\TicketOrderStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\TicketOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('renders the checkout page and displays active public tickets', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
        'status' => ProductStatus::Active,
        'visibility' => ProductVisibility::Public,
        'title' => 'General Ticket',
    ]);
    $price = $product->prices()->create([
        'name' => 'General Admission',
        'price' => 50.00,
        'capacity' => 10,
    ]);

    Livewire::test('public.events.checkout', ['event' => $event])
        ->assertSee('General Ticket')
        ->assertSee('General Admission')
        ->assertSee('$50.00');
});

it('requires a password to unlock password protected tickets', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
        'status' => ProductStatus::Active,
        'visibility' => ProductVisibility::Password,
        'password' => Hash::make('secret123'),
        'title' => 'Secret VIP Ticket',
    ]);
    $price = $product->prices()->create([
        'name' => 'Secret VIP Price',
        'price' => 150.00,
        'capacity' => 5,
    ]);

    $comp = Livewire::test('public.events.checkout', ['event' => $event])
        ->assertSee('Secret VIP Ticket')
        ->assertDontSee('Secret VIP Price'); // Not visible until unlocked

    // Enter incorrect password
    $comp->set('passwords.'.$product->product_id, 'wrongpassword')
        ->call('unlockProduct', $product->product_id)
        ->assertSee(__('Incorrect password.'));

    // Enter correct password
    $comp->set('passwords.'.$product->product_id, 'secret123')
        ->call('unlockProduct', $product->product_id)
        ->assertSee('Secret VIP Price'); // Now visible!
});

it('applies promo code successfully in live totals', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
        'status' => ProductStatus::Active,
        'visibility' => ProductVisibility::Public,
        'title' => 'Admission Ticket',
    ]);
    $price = $product->prices()->create([
        'name' => 'Regular Price',
        'price' => 100.00,
        'capacity' => 10,
    ]);

    $promoCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'HALFPRICE',
        'type' => 'percentage',
        'value' => 50,
        'status' => 'active',
    ]);

    Livewire::test('public.events.checkout', ['event' => $event])
        ->set('quantities.'.$price->product_price_id, 2) // Total: $200.00
        ->assertSee('$200.00')
        ->set('promoCodeText', 'HALFPRICE')
        ->call('applyPromoCode')
        ->assertSee('✓ Code applied: HALFPRICE')
        ->assertSee('Subtotal')
        ->assertSee('$200.00')
        ->assertSee('Discount')
        ->assertSee('-$100.00')
        ->assertSee('Total')
        ->assertSee('$100.00');
});

it('processes ticket reservation and redirects to temporary signed confirmation page', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
        'status' => ProductStatus::Active,
        'visibility' => ProductVisibility::Public,
        'title' => 'Admission Ticket',
    ]);
    $price = $product->prices()->create([
        'name' => 'Regular Price',
        'price' => 10.00,
        'capacity' => 10,
    ]);

    $component = Livewire::test('public.events.checkout', ['event' => $event])
        ->set('quantities.'.$price->product_price_id, 1)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->set('firstName', 'John')
        ->set('lastName', 'Doe')
        ->set('email', 'john@example.com')
        ->call('reserveAndCheckout')
        ->assertHasNoErrors()
        ->assertSet('step', 3);

    $orderId = $component->get('orderId');
    expect($orderId)->not->toBeNull();

    $order = TicketOrder::query()->find($orderId);
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(TicketOrderStatus::Reserved)
        ->and($order->total)->toBe(10.00)
        ->and($order->first_name)->toBe('John');

    // Simulate Payment Offline (since we are in testing environment)
    $component->call('simulatePayment')
        ->assertRedirect(); // Assert redirect happens

    $order->refresh();
    expect($order->status)->toBe(TicketOrderStatus::Completed);
});

it('secures confirmation page using Laravel temporary signed urls', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Completed,
        'total' => 50.00,
    ]);

    // Generate valid temporary signed url
    $validUrl = URL::temporarySignedRoute(
        'checkout.confirmation',
        now()->addMinutes(30),
        [
            'event' => $event->event_id,
            'ticketOrder' => $order->ticket_order_id,
        ],
    );

    // Try accessing with valid signature
    $response = $this->get($validUrl);
    $response->assertStatus(200);
    $response->assertSee($order->order_reference);

    // Try accessing with modified ticket_order parameter
    $tamperedUrl = $validUrl.'extra';
    $response = $this->get($tamperedUrl);
    $response->assertStatus(403);

    // Try accessing unsigned direct URL
    $directUrl = route('checkout.confirmation', [
        'event' => $event->event_id,
        'ticketOrder' => $order->ticket_order_id,
    ]);
    $response = $this->get($directUrl);
    $response->assertStatus(403);
});

it('expires signed urls using carbon time travel', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Completed,
    ]);

    $url = URL::temporarySignedRoute(
        'checkout.confirmation',
        now()->addMinutes(30),
        [
            'event' => $event->event_id,
            'ticketOrder' => $order->ticket_order_id,
        ],
    );

    // Access within valid window
    $this->get($url)->assertStatus(200);

    // Travel 31 minutes into the future
    $this->travel(31)->minutes();

    // Now it should return 403
    $this->get($url)->assertStatus(403);
});
