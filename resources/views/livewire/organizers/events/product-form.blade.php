<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\DataTransferObjects\Products\CreateProductDto;
use App\DataTransferObjects\Products\UpdateProductDto;
use App\Enums\PricingMode;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Event;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;
    public bool $showModal = false;
    public ?int $productId = null;

    // Form fields
    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $type = 'ticket';
    public string $pricing_mode = 'paid';
    public string $status = 'active';
    public string $visibility = 'public';
    public string $password = '';
    public int $min_qty = 1;
    public int $max_qty = 10;
    public int $sort_order = 0;

    // Price tiers
    /** @var array<int, array{name: string, price: string, capacity: string, sales_start_at: string, sales_end_at: string}> */
    public array $prices = [];

    #[On('open-product-form')]
    public function openForm(?int $productId = null): void
    {
        $this->resetErrorBag();
        $this->productId = $productId;

        if ($this->productId !== null) {
            /** @var Product $product */
            $product = Product::query()->findOrFail($this->productId);
            $this->title = $product->title;
            $this->slug = $product->slug;
            $this->description = $product->description ?? '';
            $this->type = $product->type->value;
            $this->pricing_mode = $product->pricing_mode->value;
            $this->status = $product->status->value;
            $this->visibility = $product->visibility->value;
            $this->password = ''; // Don't expose hash
            $this->min_qty = $product->min_qty;
            $this->max_qty = $product->max_qty;
            $this->sort_order = $product->sort_order;

            $this->prices = [];
            foreach ($product->prices as $price) {
                $this->prices[] = [
                    'name' => $price->name,
                    'price' => (string) $price->price,
                    'capacity' => $price->capacity !== null ? (string) $price->capacity : '',
                    'sales_start_at' => $price->sales_start_at?->format('Y-m-d\TH:i') ?? '',
                    'sales_end_at' => $price->sales_end_at?->format('Y-m-d\TH:i') ?? '',
                ];
            }
        } else {
            $this->reset([
                'title', 'slug', 'description', 'type', 'pricing_mode',
                'status', 'visibility', 'password', 'min_qty', 'max_qty', 'sort_order'
            ]);
            $this->prices = [
                [
                    'name' => __('General Admission'),
                    'price' => '0.00',
                    'capacity' => '',
                    'sales_start_at' => '',
                    'sales_end_at' => '',
                ]
            ];
        }

        $this->showModal = true;
    }

    public function updatedTitle(string $value): void
    {
        if (empty($this->slug)) {
            $this->slug = str()->slug($value);
        }
    }

    public function addPriceTier(): void
    {
        $this->prices[] = [
            'name' => '',
            'price' => '0.00',
            'capacity' => '',
            'sales_start_at' => '',
            'sales_end_at' => '',
        ];
    }

    public function removePriceTier(int $index): void
    {
        unset($this->prices[$index]);
        $this->prices = array_values($this->prices);
    }

    public function save(CreateProductAction $createAction, UpdateProductAction $updateAction): void
    {
        // Solo administradores o editores del organizador pueden mutar
        $this->authorize('update', $this->event->organizer);

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:ticket,addon,merchandise'],
            'pricing_mode' => ['required', 'string', 'in:free,paid,donation'],
            'status' => ['required', 'string', 'in:active,paused,closed'],
            'visibility' => ['required', 'string', 'in:public,hidden,password'],
            'password' => [$this->visibility === 'password' && $this->productId === null ? 'required' : 'nullable', 'string', 'min:4'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'max_qty' => ['required', 'integer', 'min:1', 'gte:min_qty'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.name' => ['required', 'string', 'max:255'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
            'prices.*.capacity' => ['nullable', 'integer', 'min:1'],
            'prices.*.sales_start_at' => ['nullable', 'date'],
            'prices.*.sales_end_at' => ['nullable', 'date', 'after_or_equal:prices.*.sales_start_at'],
        ];

        // Validar unicidad del slug en este evento
        if ($this->productId === null) {
            $rules['slug'][] = 'unique:product,slug,NULL,product_id,event_id,' . $this->event->event_id;
        } else {
            $rules['slug'][] = 'unique:product,slug,' . $this->productId . ',product_id,event_id,' . $this->event->event_id;
        }

        $this->validate($rules);

        // Mapear los precios a arrays correctos para el DTO
        $mappedPrices = array_map(function (array $tier) {
            return [
                'name' => $tier['name'],
                'price' => $this->pricing_mode === 'free' ? 0.00 : (float) $tier['price'],
                'capacity' => empty($tier['capacity']) ? null : (int) $tier['capacity'],
                'sales_start_at' => empty($tier['sales_start_at']) ? null : \Carbon\Carbon::parse($tier['sales_start_at']),
                'sales_end_at' => empty($tier['sales_end_at']) ? null : \Carbon\Carbon::parse($tier['sales_end_at']),
            ];
        }, $this->prices);

        /** @var User $user */
        $user = auth()->user();

        if ($this->productId !== null) {
            /** @var Product $product */
            $product = Product::query()->findOrFail($this->productId);
            $dto = new UpdateProductDto(
                title: $this->title,
                slug: $this->slug,
                description: empty($this->description) ? null : $this->description,
                type: ProductType::from($this->type),
                pricing_mode: PricingMode::from($this->pricing_mode),
                status: ProductStatus::from($this->status),
                visibility: ProductVisibility::from($this->visibility),
                password: empty($this->password) ? null : $this->password,
                min_qty: $this->min_qty,
                max_qty: $this->max_qty,
                sort_order: $this->sort_order,
                prices: $mappedPrices
            );

            $updateAction($product, $dto, $user);
            session()->flash('success', __('Product updated successfully.'));
        } else {
            $dto = new CreateProductDto(
                title: $this->title,
                slug: $this->slug,
                description: empty($this->description) ? null : $this->description,
                type: ProductType::from($this->type),
                pricing_mode: PricingMode::from($this->pricing_mode),
                status: ProductStatus::from($this->status),
                visibility: ProductVisibility::from($this->visibility),
                password: empty($this->password) ? null : $this->password,
                min_qty: $this->min_qty,
                max_qty: $this->max_qty,
                sort_order: $this->sort_order,
                prices: $mappedPrices
            );

            $createAction($this->event, $dto, $user);
            session()->flash('success', __('Product created successfully.'));
        }

        $this->showModal = false;
        $this->dispatch('product-saved');
    }
};
?>

<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-950/80" aria-hidden="true" @click="$wire.set('showModal', false)"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                            {{ $productId ? __('Edit Product / Ticket') : __('Create Product / Ticket') }}
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" @click="$wire.set('showModal', false)">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="p-6 space-y-6">
                        {{-- Row 1: Title & Slug --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                                <input type="text" wire:model.live="title" id="title" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="{{ __('e.g. VIP Entrance') }}">
                                @error('title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Slug') }}</label>
                                <input type="text" wire:model="slug" id="slug" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                @error('slug') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                            <textarea wire:model="description" id="description" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="{{ __('Provide details about access, inclusions, etc.') }}"></textarea>
                            @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        {{-- Type & Pricing Mode --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Product Type') }}</label>
                                <select wire:model.live="type" id="type" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="ticket">{{ __('Ticket (Admission)') }}</option>
                                    <option value="addon">{{ __('Addon (Complement)') }}</option>
                                    <option value="merchandise">{{ __('Merchandise') }}</option>
                                </select>
                                @error('type') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="pricing_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Pricing Mode') }}</label>
                                <select wire:model.live="pricing_mode" id="pricing_mode" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="paid">{{ __('Paid') }}</option>
                                    <option value="free">{{ __('Free') }}</option>
                                    <option value="donation">{{ __('Donation (Pay what you want)') }}</option>
                                </select>
                                @error('pricing_mode') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Prices Section --}}
                        <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Price Tiers & Capacities') }}</h4>
                                @if($pricing_mode !== 'donation')
                                    <button type="button" wire:click="addPriceTier" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                        <svg class="size-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {{ __('Add Tier') }}
                                    </button>
                                @endif
                            </div>

                            <div class="space-y-4">
                                @foreach($prices as $index => $price)
                                    <div class="grid gap-3 sm:grid-cols-12 items-end border border-gray-100 p-3 rounded-lg dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                                        <div class="sm:col-span-5">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Tier Name') }}</label>
                                            <input type="text" wire:model="prices.{{ $index }}.name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            @error("prices.{$index}.name") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Price') }}</label>
                                            <div class="relative mt-1">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                                                    <span class="text-xs text-gray-500">$</span>
                                                </div>
                                                <input type="number" step="0.01" wire:model="prices.{{ $index }}.price" @disabled($pricing_mode === 'free' || $pricing_mode === 'donation') class="block w-full rounded-lg border border-gray-300 pl-6 pr-3 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800/50">
                                            </div>
                                            @error("prices.{$index}.price") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Capacity') }}</label>
                                            <input type="number" wire:model="prices.{{ $index }}.capacity" placeholder="{{ __('Unlimited') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            @error("prices.{$index}.capacity") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="sm:col-span-1 text-right">
                                            @if(count($prices) > 1)
                                                <button type="button" wire:click="removePriceTier({{ $index }})" class="p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Availability Tiers --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Min Qty Per Order') }}</label>
                                <input type="number" wire:model="min_qty" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                @error('min_qty') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Max Qty Per Order') }}</label>
                                <input type="number" wire:model="max_qty" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                @error('max_qty') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Status & Visibility --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
                                <select wire:model="status" id="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="active">{{ __('Active (On sale)') }}</option>
                                    <option value="paused">{{ __('Paused (Suspended)') }}</option>
                                    <option value="closed">{{ __('Closed (Out of sale)') }}</option>
                                </select>
                                @error('status') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="visibility" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Visibility') }}</label>
                                <select wire:model.live="visibility" id="visibility" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="public">{{ __('Public') }}</option>
                                    <option value="hidden">{{ __('Hidden') }}</option>
                                    <option value="password">{{ __('Password Protected') }}</option>
                                </select>
                                @error('visibility') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Password field (conditional) --}}
                        @if($visibility === 'password')
                            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/30 dark:bg-yellow-950/20">
                                <label for="password" class="block text-sm font-medium text-yellow-800 dark:text-yellow-400">{{ __('Access Password') }}</label>
                                <input type="password" wire:model="password" id="password" class="mt-1 block w-full rounded-lg border border-yellow-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-yellow-800 dark:bg-gray-800 dark:text-white" placeholder="{{ $productId ? __('Leave blank to keep current password') : __('Enter access password') }}">
                                @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- Sort Order --}}
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Display Order') }}</label>
                            <input type="number" wire:model="sort_order" id="sort_order" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @error('sort_order') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        {{-- Form actions --}}
                        <div class="border-t border-gray-100 pt-4 dark:border-gray-800 flex justify-end gap-3">
                            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="$wire.set('showModal', false)">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
