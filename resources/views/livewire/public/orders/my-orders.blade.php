<?php

declare(strict_types=1);

namespace App\Livewire\Public\Orders;

use App\Actions\Orders\SendAccessLinkAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    #[Validate('required|email')]
    public string $email = '';

    public bool $sent = false;

    public function requestLink(SendAccessLinkAction $sendAccessLinkAction): void
    {
        $this->validate();

        // Llamar a la acción de envío de enlace
        $sendAccessLinkAction($this->email);

        $this->sent = true;
    }
};
?>

<div class="max-w-md mx-auto my-12 rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
    <div class="text-center space-y-2">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ __('Access My Tickets') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Retrieve and download all your ticket orders securely.') }}
        </p>
    </div>

    @if ($sent)
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-950/20 dark:border-blue-900/30 dark:text-blue-400 text-sm space-y-2">
            <p class="font-bold">{{ __('Link Sent!') }}</p>
            <p>
                {{ __('If there are orders associated with the email address you provided, you will receive an email shortly containing a secure, single-use link to access them.') }}
            </p>
            <p class="text-xs text-blue-600 dark:text-blue-500 italic mt-2">
                {{ __('Please verify your spam folder if the email does not arrive in a few minutes. The link is only valid for 15 minutes.') }}
            </p>
        </div>
    @else
        <form wire:submit.prevent="requestLink" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Email Address') }}
                </label>
                <div class="mt-1">
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        placeholder="you@example.com"
                        required
                    >
                </div>
                @error('email')
                    <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full inline-flex justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                {{ __('Send Access Link') }}
            </button>
        </form>
    @endif
</div>
