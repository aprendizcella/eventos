<?php

declare(strict_types=1);

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\DataTransferObjects\Products\CreateProductDto;
use App\DataTransferObjects\Products\UpdateProductDto;
use App\Enums\PricingMode;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

function attachRole(Organizer $organizer, User $user, OrganizerRoles $role): void
{
    $organizer->users()->attach($user->id, ['role' => $role->value]);
}

it('creates a product and prices using CreateProductAction', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $creator = User::factory()->create();

    $dto = new CreateProductDto(
        title: 'VIP Admission',
        slug: 'vip-admission',
        description: 'VIP benefits included',
        type: ProductType::Ticket,
        pricing_mode: PricingMode::Paid,
        status: ProductStatus::Active,
        visibility: ProductVisibility::Public,
        password: null,
        min_qty: 1,
        max_qty: 5,
        sort_order: 1,
        prices: [
            [
                'name' => 'Early Bird',
                'price' => 75.00,
                'capacity' => 100,
                'sales_start_at' => null,
                'sales_end_at' => null,
            ],
        ],
    );

    $action = resolve(CreateProductAction::class);
    $product = $action($event, $dto, $creator);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->title)->toBe('VIP Admission')
        ->and($product->slug)->toBe('vip-admission')
        ->and($product->organizer_id)->toBe($organizer->id)
        ->and($product->event_id)->toBe($event->event_id)
        ->and($product->min_qty)->toBe(1)
        ->and($product->max_qty)->toBe(5);

    $this->assertDatabaseHas('product', [
        'product_id' => $product->product_id,
        'title' => 'VIP Admission',
        'organizer_id' => $organizer->id,
    ]);

    $this->assertDatabaseHas('product_price', [
        'product_id' => $product->product_id,
        'name' => 'Early Bird',
        'price' => 75.00,
        'capacity' => 100,
    ]);
});

it('updates a product and syncs prices using UpdateProductAction', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $updater = User::factory()->create();

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'organizer_id' => $organizer->id,
        'title' => 'Old Title',
    ]);
    $product->prices()->create([
        'name' => 'Old Price',
        'price' => 50.00,
        'capacity' => 50,
    ]);

    $dto = new UpdateProductDto(
        title: 'New Title',
        slug: 'new-title',
        description: 'New desc',
        type: ProductType::Addon,
        pricing_mode: PricingMode::Paid,
        status: ProductStatus::Paused,
        visibility: ProductVisibility::Public,
        password: null,
        min_qty: 2,
        max_qty: 8,
        sort_order: 2,
        prices: [
            [
                'name' => 'New Price Tier',
                'price' => 99.99,
                'capacity' => 200,
                'sales_start_at' => null,
                'sales_end_at' => null,
            ],
        ],
    );

    $action = resolve(UpdateProductAction::class);
    $updatedProduct = $action($product, $dto, $updater);

    expect($updatedProduct->title)->toBe('New Title')
        ->and($updatedProduct->status)->toBe(ProductStatus::Paused)
        ->and($updatedProduct->min_qty)->toBe(2)
        ->and($updatedProduct->max_qty)->toBe(8);

    // Old prices should be deleted
    $this->assertDatabaseMissing('product_price', [
        'name' => 'Old Price',
    ]);

    // New prices should exist
    $this->assertDatabaseHas('product_price', [
        'product_id' => $product->product_id,
        'name' => 'New Price Tier',
        'price' => 99.99,
        'capacity' => 200,
    ]);
});

it('hashes the password when visibility is password', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $creator = User::factory()->create();

    $dto = new CreateProductDto(
        title: 'Secret Entry',
        slug: 'secret-entry',
        description: null,
        type: ProductType::Ticket,
        pricing_mode: PricingMode::Free,
        status: ProductStatus::Active,
        visibility: ProductVisibility::Password,
        password: 'my-super-secret-password',
        min_qty: 1,
        max_qty: 10,
        sort_order: 0,
        prices: [
            [
                'name' => 'Free Secret',
                'price' => 0.00,
                'capacity' => null,
                'sales_start_at' => null,
                'sales_end_at' => null,
            ],
        ],
    );

    $action = resolve(CreateProductAction::class);
    $product = $action($event, $dto, $creator);

    expect($product->password)->not->toBe('my-super-secret-password')
        ->and(Hash::check('my-super-secret-password', $product->password))->toBeTrue();
});

it('renders list component and can add product via form component', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $admin = User::factory()->create();
    attachRole($organizer, $admin, OrganizerRoles::Admin);

    $this->actingAs($admin);

    // Assert list renders empty state initially
    Livewire::test('organizers.events.product-list', ['event' => $event])
        ->assertSee(__('No Tickets or Products Created'));

    // Assert form can submit successfully
    Livewire::test('organizers.events.product-form', ['event' => $event])
        ->call('openForm')
        ->set('title', 'Ticket de Prueba')
        ->set('slug', 'ticket-de-prueba')
        ->set('prices', [
            [
                'name' => 'General',
                'price' => '25.00',
                'capacity' => '150',
                'sales_start_at' => '',
                'sales_end_at' => '',
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('product-saved');

    $this->assertDatabaseHas('product', [
        'event_id' => $event->event_id,
        'title' => 'Ticket de Prueba',
        'slug' => 'ticket-de-prueba',
    ]);
});

it('denies product manipulation to viewers', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $viewer = User::factory()->create();
    attachRole($organizer, $viewer, OrganizerRoles::Viewer);

    $this->actingAs($viewer);

    Livewire::test('organizers.events.product-form', ['event' => $event])
        ->call('openForm')
        ->set('title', 'Intento Fallido')
        ->set('slug', 'intento-fallido')
        ->set('prices', [
            [
                'name' => 'General',
                'price' => '25.00',
                'capacity' => '150',
                'sales_start_at' => '',
                'sales_end_at' => '',
            ],
        ])
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseMissing('product', [
        'title' => 'Intento Fallido',
    ]);
});
