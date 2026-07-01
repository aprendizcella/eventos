<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\TicketOrderStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('allows offline payment simulation in testing environment', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'total' => 50.00,
    ]);

    expect(app()->environment('testing'))->toBeTrue();

    // Call simulate payment via Livewire
    Livewire::test('public.events.checkout', ['event' => $event])
        ->set('orderId', $order->ticket_order_id)
        ->call('simulatePayment')
        ->assertRedirect(); // Should succeed and redirect

    $order->refresh();
    expect($order->status)->toBe(TicketOrderStatus::Completed);

    // Reset status for second simulation
    $order->update(['status' => TicketOrderStatus::Reserved]);

    Livewire::test('public.events.checkout', ['event' => $event])
        ->set('orderId', $order->ticket_order_id)
        ->call('simulateStripeWebhookPayment')
        ->assertRedirect();

    $order->refresh();
    expect($order->status)->toBe(TicketOrderStatus::Completed);
});

it('denies offline payment simulation in production environment', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $order = TicketOrder::factory()->create([
        'event_id' => $event->event_id,
        'status' => TicketOrderStatus::Reserved,
        'total' => 50.00,
    ]);

    // Force environment to production
    app()->detectEnvironment(fn () => 'production');
    expect(app()->environment('local', 'testing'))->toBeFalse();

    // Calling simulatePayment should fail with 403 Forbidden
    Livewire::test('public.events.checkout', ['event' => $event])
        ->set('orderId', $order->ticket_order_id)
        ->call('simulatePayment')
        ->assertForbidden();

    Livewire::test('public.events.checkout', ['event' => $event])
        ->set('orderId', $order->ticket_order_id)
        ->call('simulateStripeWebhookPayment')
        ->assertForbidden();

    $order->refresh();
    expect($order->status)->toBe(TicketOrderStatus::Reserved); // Unchanged
});
