<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Actions\Waitlist\JoinWaitlistAction;
use App\Exceptions\Waitlist\WaitlistException;
use App\Models\Event;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public Event $event;
    public ?int $selectedPriceId = null;

    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';

    public bool $success = false;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $priceId = request()->query('price');
        if ($priceId !== null) {
            $this->selectedPriceId = (int) $priceId;
        }
    }

    public function join(JoinWaitlistAction $action): void
    {
        $this->validate([
            'selectedPriceId' => ['required', 'integer', 'exists:product_price,product_price_id'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $normalizedEmail = strtolower(trim($this->email));
        $ip = request()->ip() ?? '127.0.0.1';

        // Rate limiter clave compuesta
        $limiterKey = "join-waitlist:{$this->event->event_id}:{$this->selectedPriceId}:{$normalizedEmail}:{$ip}";

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $this->addError('join', __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]));
            return;
        }

        RateLimiter::hit($limiterKey, 60);

        try {
            $action(
                eventId: $this->event->event_id,
                productPriceId: $this->selectedPriceId,
                email: $normalizedEmail,
                firstName: $this->firstName,
                lastName: $this->lastName
            );

            $this->success = true;
            $this->reset(['firstName', 'lastName', 'email']);
        } catch (WaitlistException $e) {
            $this->addError('join', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('join', __('An unexpected error occurred. Please try again.'));
        }
    }

    public function with(): array
    {
        // Obtener los precios/tiers del evento que tienen capacidad limitada (para los que tiene sentido una lista de espera)
        $prices = ProductPrice::query()
            ->join('product', 'product_price.product_id', '=', 'product.product_id')
            ->where('product.event_id', $this->event->event_id)
            ->whereNotNull('product_price.capacity')
            ->get();

        return [
            'prices' => $prices,
        ];
    }
};
?>

<div class="max-w-md mx-auto">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ __('Join the Waitlist') }}</h2>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Get notified as soon as a ticket opens up for :event.', ['event' => $event->title]) }}
            </p>
        </div>

        @if($success)
            <div class="rounded-lg bg-green-50 p-4 text-center text-sm text-green-800 dark:bg-green-950/30 dark:text-green-400 font-semibold space-y-2">
                <span class="text-3xl block">🎉</span>
                <p>{{ __('You have successfully joined the waitlist!') }}</p>
                <p class="text-xs text-green-600/80 dark:text-green-400/80 font-medium">
                    {{ __('We will send you an email if a ticket becomes available.') }}
                </p>
                <div class="pt-2">
                    <a href="{{ route('checkout', ['event' => $event->event_id]) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                        &larr; {{ __('Back to Event') }}
                    </a>
                </div>
            </div>
        @else
            @error('join')
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/30 dark:text-red-400 font-semibold">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit.prevent="join" class="space-y-4">
                <div>
                    <label for="price" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Select Ticket Tier') }} *</label>
                    <select wire:model="selectedPriceId" id="price" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">{{ __('Choose a ticket option') }}</option>
                        @foreach($prices as $price)
                            <option value="{{ $price->product_price_id }}">
                                {{ $price->product->title }} - {{ $price->name }} (${{ number_format($price->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('selectedPriceId') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="firstName" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('First Name') }} *</label>
                        <input type="text" wire:model="firstName" id="firstName" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('firstName') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="lastName" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Last Name') }} *</label>
                        <input type="text" wire:model="lastName" id="lastName" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('lastName') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Email Address') }} *</label>
                    <input type="email" wire:model="email" id="email" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="you@example.com">
                    @error('email') <span class="text-xxs text-red-600 font-medium">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-500">
                    {{ __('Join Waitlist') }}
                </button>

                <div class="text-center pt-2">
                    <a href="{{ route('checkout', ['event' => $event->event_id]) }}" class="text-xxs text-gray-500 dark:text-gray-400 hover:underline">
                        &larr; {{ __('Back to Event') }}
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>
