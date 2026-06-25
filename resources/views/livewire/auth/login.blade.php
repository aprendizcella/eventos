<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('login.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Sign In</h1>

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

    <div class="mb-6">
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

    <button type="submit" class="w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2">
        Sign In
    </button>

    <p class="text-sm text-center mt-4">
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Create an account</a>
    </p>
</form>
