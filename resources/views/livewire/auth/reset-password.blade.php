<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('password.reset.post') }}" method="POST">
    @csrf

    <input type="hidden" name="token" value="{{ request()->route('token') }}" />

    <h1 class="text-2xl font-semibold mb-6">Set New Password</h1>

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium mb-1">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email', request()->string('email')->toString()) }}"
            required
            autofocus
            class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2"
        />
        @error('email')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium mb-1">Password</label>
        <input
            id="password"
            type="password"
            name="password"
            required
            class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2"
        />
        @error('password')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
            class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2"
        />
    </div>

    <button type="submit" class="w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2">
        Reset Password
    </button>
</form>
