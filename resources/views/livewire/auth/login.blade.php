<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('login.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Sign In</h1>

    <x-form.field name="email" label="Email" type="email" :value="old('email')" required autofocus />
    <x-form.password-input name="password" label="Password" required autocomplete="current-password" />

    <x-form.checkbox name="remember" label="Remember me" />

    <x-ui.button>Sign In</x-ui.button>

    <p class="text-sm flex items-center justify-between gap-4 mt-4 px-4">
        <x-ui.link :href="route('register')">Create an account</x-ui.link>
        <x-ui.link :href="route('forgot-password')">Forgot your password?</x-ui.link>
    </p>
</form>
