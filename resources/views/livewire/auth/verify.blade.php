<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;

layout('layouts.auth');

mount(function () {
    if (auth()->user()->hasVerifiedEmail()) {
        return redirect()->route('dashboard');
    }
});
?>

<div>
    <h1 class="text-2xl font-semibold mb-4">Verify Your Email Address</h1>

    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 text-sm text-green-600 dark:text-green-400">
            Another verification link has been sent to your email address.
        </p>
    @endif

    <p class="mb-6 text-sm">
        Thanks for signing up! Before getting started, could you verify your email address by clicking
        on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
    </p>

    <div class="flex items-center justify-between gap-4">
        <form action="{{ route('verification.send') }}" method="POST">
            @csrf

            <x-ui.button type="submit">
                Resend Verification Email
            </x-ui.button>
        </form>

        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <x-ui.button type="submit">
                Log Out
            </x-ui.button>
        </form>
    </div>
</div>
