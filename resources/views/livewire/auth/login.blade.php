<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('login.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Sign In</h1>

    <x-auth.field name="email" label="Email" type="email" :value="old('email')" required autofocus />
    <x-auth.password-input name="password" label="Password" required autocomplete="current-password" />

    <x-auth.button>Sign In</x-auth.button>

    <p class="text-sm flex items-center justify-between gap-4 mt-4 px-4">
        <x-auth.link :href="route('register')">Create an account</x-auth.link>
        <x-auth.link :href="route('forgot-password')">Forgot your password?</x-auth.link>
    </p>
</form>
