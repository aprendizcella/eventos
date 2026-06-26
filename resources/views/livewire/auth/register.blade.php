<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('register.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Create Account</h1>

    <x-form.field name="name" label="Name" :value="old('name')" required autofocus />
    <x-form.field name="email" label="Email" type="email" :value="old('email')" required />
    <x-form.password-input name="password" label="Password" required autocomplete="new-password" />
    <x-form.password-input name="password_confirmation" label="Confirm password" required autocomplete="new-password" />

    <x-ui.button>Register</x-ui.button>

    <p class="text-sm text-center mt-4">
        <x-ui.link :href="route('login')">Already have an account?</x-ui.link>
    </p>
</form>