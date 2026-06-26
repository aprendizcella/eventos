<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('register.post') }}" method="POST">
    @csrf

    <h1 class="text-2xl font-semibold mb-6">Create Account</h1>

    <x-auth.field name="name" label="Name" :value="old('name')" required autofocus />
    <x-auth.field name="email" label="Email" type="email" :value="old('email')" required />
    <x-auth.password-input name="password" label="Password" required autocomplete="new-password" />
    <x-auth.password-input name="password_confirmation" label="Confirm password" required autocomplete="new-password" />

    <x-auth.button>Register</x-auth.button>

    <p class="text-sm text-center mt-4">
        <x-auth.link :href="route('login')">Already have an account?</x-auth.link>
    </p>
</form>