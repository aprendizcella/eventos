<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// ─── Event Billing Settings ───────────────────────────────────────────────

it('allows organizer admin to save billing settings for an event', function (): void {
    $admin = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->actingAs($admin);

    Volt::test('organizers.events.event-settings', ['event' => $event])
        ->set('invoice_enabled', true)
        ->set('invoice_notes', 'Payment due within 30 days.')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $event->refresh();
    expect($event->settings['invoice_enabled'])->toBe(true)
        ->and($event->settings['invoice_notes'])->toBe('Payment due within 30 days.');
});

it('disables invoice billing for an event', function (): void {
    $admin = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'settings' => ['invoice_enabled' => true],
    ]);

    $this->actingAs($admin);

    Volt::test('organizers.events.event-settings', ['event' => $event])
        ->assertSet('invoice_enabled', true)
        ->set('invoice_enabled', false)
        ->call('saveSettings')
        ->assertHasNoErrors();

    $event->refresh();
    expect($event->settings['invoice_enabled'])->toBe(false);
});

// ─── Organizer Billing Settings ───────────────────────────────────────────

it('allows organizer admin to save tax and fee settings', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', $organizer->name)
        ->set('slug', $organizer->slug)
        ->set('tax_name', 'IVA')
        ->set('tax_rate', '21')
        ->set('tax_id', 'ES12345678Z')
        ->set('platform_fee_percentage', '5')
        ->set('platform_fee_fixed', '99')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $organizer->refresh();
    expect($organizer->settings['billing']['tax_name'])->toBe('IVA')
        ->and($organizer->settings['billing']['tax_rate'])->toBe(2100)
        ->and($organizer->settings['billing']['tax_id'])->toBe('ES12345678Z')
        ->and($organizer->settings['billing']['platform_fee_percentage'])->toBe(500)
        ->and($organizer->settings['billing']['platform_fee_fixed'])->toBe(99);
});

it('denies billing settings updates to unauthorized users', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Viewer->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', $organizer->name)
        ->set('slug', $organizer->slug)
        ->set('tax_name', 'VAT')
        ->call('saveSettings')
        ->assertForbidden();
});

it('validates tax rate range', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($user);

    Volt::test('organizers.settings', ['organizer' => $organizer])
        ->set('name', $organizer->name)
        ->set('slug', $organizer->slug)
        ->set('tax_rate', '150')
        ->call('saveSettings')
        ->assertHasErrors('tax_rate');
});
