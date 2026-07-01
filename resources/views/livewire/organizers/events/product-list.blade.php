<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Models\Event;
use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;

    #[On('product-saved')]
    public function refreshList(): void
    {
        // No action needed, Livewire automatically updates on state change
    }

    public function deleteProduct(int $productId): void
    {
        $this->authorize('update', $this->event->organizer);

        /** @var Product $product */
        $product = Product::query()
            ->where('event_id', $this->event->event_id)
            ->findOrFail($productId);

        $product->delete();

        session()->flash('success', __('Product deleted successfully.'));
    }

    public function with(): array
    {
        $products = Product::query()
            ->where('event_id', $this->event->event_id)
            ->orderBy('sort_order')
            ->orderBy('product_id')
            ->with('prices')
            ->get();

        return [
            'products' => $products,
        ];
    }
};
?>

<div>
    {{-- Success Message --}}
    @if(session()->has('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header Actions --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Tickets & Products') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Define admission ticket classes, add-ons, or merchandise for sale.') }}</p>
        </div>
        <button type="button" @click="$dispatch('open-product-form')" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="size-5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('Add Product / Ticket') }}
        </button>
    </div>

    {{-- Product List Container --}}
    @if($products->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900 py-16">
            <span class="text-5xl">🎟️</span>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('No Tickets or Products Created') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                {{ __('Create admission tickets, merchandise, or parking passes to start selling registrations for this event.') }}
            </p>
            <div class="mt-6">
                <button type="button" @click="$dispatch('open-product-form')" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    <svg class="size-5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Create First Ticket') }}
                </button>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('Name') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Type & Pricing') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Sales / Capacity') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($products as $product)
                            @php
                                $totalCapacity = 0;
                                $totalSold = 0;
                                $isUnlimited = false;

                                foreach ($product->prices as $price) {
                                    $totalSold += $price->quantity_sold;
                                    if ($price->capacity === null) {
                                        $isUnlimited = true;
                                    } else {
                                        $totalCapacity += $price->capacity;
                                    }
                                }

                                $pricingText = '';
                                if ($product->pricing_mode->value === 'free') {
                                    $pricingText = __('Free');
                                } elseif ($product->pricing_mode->value === 'donation') {
                                    $pricingText = __('Donation');
                                } else {
                                    $pricesArray = $product->prices->pluck('price')->toArray();
                                    if (count($pricesArray) > 1) {
                                        $pricingText = '$' . min($pricesArray) . ' - $' . max($pricesArray);
                                    } elseif (count($pricesArray) === 1) {
                                        $pricingText = '$' . $pricesArray[0];
                                    } else {
                                        $pricingText = '$0.00';
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $product->title }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $product->description }}</div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        @if($product->visibility->value === 'password')
                                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xxs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-950/30 dark:text-yellow-400">
                                                🔒 {{ __('Password Protected') }}
                                            </span>
                                        @elseif($product->visibility->value === 'hidden')
                                            <span class="inline-flex items-center rounded-md bg-gray-50 px-1.5 py-0.5 text-xxs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400">
                                                👁️‍🗨️ {{ __('Hidden') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            @if($product->type->value === 'ticket')
                                                🎫 {{ __('Ticket') }}
                                            @elseif($product->type->value === 'addon')
                                                🔌 {{ __('Add-on') }}
                                            @else
                                                📦 {{ __('Merchandise') }}
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $pricingText }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                                            {{ $totalSold }} / {{ $isUnlimited ? __('Unlimited') : $totalCapacity }}
                                        </div>
                                        @if(!$isUnlimited && $totalCapacity > 0)
                                            @php $pct = min(100, ($totalSold / $totalCapacity) * 100); @endphp
                                            <div class="w-24 bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                                <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($totalCapacity > 0 && $totalSold >= $totalCapacity && !$isUnlimited)
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-950/30 dark:text-red-400">
                                            {{ __('Sold Out') }}
                                        </span>
                                    @elseif($product->status->value === 'active')
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950 dark:text-green-300">
                                            {{ __('Active') }}
                                        </span>
                                    @elseif($product->status->value === 'paused')
                                        <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-950/30 dark:text-yellow-400">
                                            {{ __('Paused') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400">
                                            {{ __('Closed') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button" @click="$dispatch('open-product-form', { productId: {{ $product->product_id }} })" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ __('Edit') }}
                                        </button>
                                        <button type="button" wire:confirm="{{ __('Are you sure you want to delete this product? This action cannot be undone.') }}" wire:click="deleteProduct({{ $product->product_id }})" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Form Modal Component --}}
    <livewire:organizers.events.product-form :event="$event" />
</div>
