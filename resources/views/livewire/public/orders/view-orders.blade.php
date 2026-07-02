<?php

declare(strict_types=1);

namespace App\Livewire\Public\Orders;

use App\Models\TicketOrder;
use App\Services\Tickets\TicketPdfGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public ?string $email = null;

    /** @var Collection<int, TicketOrder> */
    public Collection $orders;

    public function mount(): void
    {
        $token = request()->query('token');

        // 1. Validar la firma y el token
        if (!request()->hasValidSignature()) {
            if (!session()->has('verified_ticket_email')) {
                abort(403, 'Invalid or expired signature.');
            }
        } else {
            if ($token) {
                // Invalidación atómica real (pull)
                $cachedEmail = Cache::pull('magic_access_token_' . $token);

                if (!$cachedEmail) {
                    abort(403, 'This magic link has already been used or has expired.');
                }

                session()->put('verified_ticket_email', $cachedEmail);
            }
        }

        $this->email = session()->get('verified_ticket_email');

        if (!$this->email) {
            abort(403, 'Unauthorized access.');
        }

        // Cargar las órdenes completas de este email
        $this->orders = TicketOrder::query()
            ->with(['event.organizer', 'items.product', 'attendees'])
            ->where('email', $this->email)
            ->where('status', 'completed')
            ->latest()
            ->get();
    }

    public function downloadPdf(int $orderId, TicketPdfGenerator $pdfGenerator)
    {
        /** @var TicketOrder $order */
        $order = TicketOrder::query()
            ->with('attendees')
            ->where('ticket_order_id', $orderId)
            ->where('email', $this->email)
            ->firstOrFail();

        return response()->streamDownload(function () use ($order, $pdfGenerator) {
            echo $pdfGenerator->generateForAttendees($order->attendees);
        }, 'tickets-' . $order->order_reference . '.pdf');
    }
};
?>

<div class="max-w-4xl mx-auto my-12 p-6 space-y-8">
    <div class="border-b border-gray-200 pb-5 dark:border-gray-800">
        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ __('My Orders & Tickets') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
            {{ __('Showing orders for') }} <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $email }}</span>
        </p>
    </div>

    @if ($orders->isEmpty())
        <div class="p-6 text-center border border-dashed border-gray-200 rounded-2xl dark:border-gray-800">
            <p class="text-gray-500 dark:text-gray-400">{{ __('No confirmed ticket orders found for this email address.') }}</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($orders as $order)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-150 pb-4 dark:border-gray-800 gap-4">
                        <div>
                            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">{{ $order->event->title }}</span>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                                {{ __('Order:') }} {{ $order->order_reference }}
                            </h3>
                            <span class="text-xs text-gray-400 block mt-1">
                                {{ __('Placed on:') }} {{ $order->created_at?->format('F d, Y') }}
                            </span>
                        </div>
                        <button
                            type="button"
                            wire:click="downloadPdf({{ $order->ticket_order_id }})"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-500"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ __('Download PDF Tickets') }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Billing details') }}</h4>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <p><span class="text-gray-400">{{ __('Name:') }}</span> {{ $order->first_name }} {{ $order->last_name }}</p>
                                <p><span class="text-gray-400">{{ __('Email:') }}</span> {{ $order->email }}</p>
                                <p><span class="text-gray-400">{{ __('Total Paid:') }}</span> ${{ number_format($order->total, 2) }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Tickets Issued') }}</h4>
                            <div class="space-y-1.5">
                                @foreach ($order->attendees as $attendee)
                                    <div class="flex justify-between items-center text-sm border border-gray-100 rounded-lg p-2.5 bg-gray-50 dark:bg-gray-800/30 dark:border-gray-850">
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">
                                                {{ $attendee->first_name }} {{ $attendee->last_name }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ $attendee->email }}</p>
                                        </div>
                                        <span class="font-mono text-xs font-bold text-gray-600 dark:text-gray-400 bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded">
                                            {{ $attendee->unique_code }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
