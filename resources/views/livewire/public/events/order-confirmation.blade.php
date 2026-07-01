<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Models\Event;
use App\Models\TicketOrder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public Event $event;
    public TicketOrder $ticketOrder;

    public function mount(Event $event, TicketOrder $ticketOrder): void
    {
        $this->event = $event;
        $this->ticketOrder = $ticketOrder;

        // Validar correspondencia
        if ($this->ticketOrder->event_id !== $this->event->event_id) {
            abort(404);
        }

        // Si no está completada/comprada, no debe mostrarse como confirmación exitosa
        if ($this->ticketOrder->status->value !== 'completed') {
            abort(403, 'Order has not been finalized yet.');
        }
    }
};
?>

<div class="max-w-2xl mx-auto rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900 text-center space-y-6">
    <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-950/30 dark:text-green-400">
        <span class="text-3xl">✓</span>
    </div>

    <div>
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ __('Thank you for your order!') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            {{ __('Your tickets have been confirmed and are ready.') }}
        </p>
    </div>

    <div class="border-t border-b border-gray-150 py-4 dark:border-gray-800 space-y-3 text-left max-w-md mx-auto text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">{{ __('Order Reference') }}</span>
            <span class="font-bold text-gray-900 dark:text-white">{{ $ticketOrder->order_reference }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">{{ __('Buyer') }}</span>
            <span class="font-semibold text-gray-900 dark:text-white">{{ $ticketOrder->first_name }} {{ $ticketOrder->last_name }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">{{ __('Email') }}</span>
            <span class="text-gray-900 dark:text-white">{{ $ticketOrder->email }}</span>
        </div>
        <div class="flex justify-between border-t border-dashed border-gray-150 pt-3 dark:border-gray-850">
            <span class="text-gray-500 font-semibold">{{ __('Total Paid') }}</span>
            <span class="font-bold text-gray-900 dark:text-white">${{ number_format($ticketOrder->total, 2) }}</span>
        </div>
    </div>

    <div class="space-y-4 text-left max-w-md mx-auto">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Tickets Summary') }}</h3>
        <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-100 rounded-lg p-3 bg-gray-50 dark:bg-gray-800/20 dark:border-gray-850">
            @foreach($ticketOrder->items as $item)
                <div class="py-2.5 flex justify-between text-sm first:pt-0 last:pb-0">
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item->quantity }}x</span>
                        <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $item->product->title }}</span>
                        @if($item->productPrice)
                            <span class="text-xs text-gray-400 block mt-0.5">({{ $item->productPrice->name }})</span>
                        @endif
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        ${{ number_format($item->total, 2) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
        <p class="text-xs text-gray-400 italic">
            {{ __('A confirmation email has been sent to') }} {{ $ticketOrder->email }}.
        </p>
    </div>
</div>
