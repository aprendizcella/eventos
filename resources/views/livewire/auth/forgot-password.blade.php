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

    <x-auth.field name="email" label="Email" type="email" :value="old('email')" required autofocus />

    @if (session('status'))
        <p class="text-sm text-green-600 mb-4">{{ __(session('status')) }}</p>
    @endif

    <x-auth.button>Send Reset Link</x-auth.button>

    <p class="text-sm text-center mt-4">
        <x-auth.link :href="route('login')">Back to sign in</x-auth.link>
    </p>
</form>