<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('renders the bulk message tab with reusable form components', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $product = Product::factory()->create(['event_id' => $event->event_id]);
    ProductPrice::factory()->create([
        'product_id' => $product->product_id,
        'name' => 'General Admission',
    ]);

    $this->actingAs($user);

    Volt::test('organizers.events.bulk-message', ['event' => $event])
        ->assertSee('Compose Message')
        ->assertSee('Campaign History')
        ->assertSee('{{first_name}}', false)
        ->assertSee('name="subject"', false)
        ->assertSee('name="body"', false)
        ->assertSee('name="productPriceId"', false)
        ->assertSee('name="attendeeStatus"', false)
        ->assertSee('name="checkInStatus"', false)
        ->assertSee('rounded-xl border border-gray-200 bg-white shadow-sm', false)
        ->assertSee('Recipient Segmentation');
});
