<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('forgot-password.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Reset Password</h1>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Enter your email address and we will send you a password reset link.
    </p>

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium mb-1">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
            class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2"
        />
        @error('email')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    @if (session('status'))
        <p class="text-sm text-green-600 mb-4">{{ __(session('status')) }}</p>
    @endif

    <button type="submit" class="w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2">
        Send Reset Link
    </button>

    <p class="text-sm text-center mt-4">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Back to sign in</a>
    </p>
</form>
