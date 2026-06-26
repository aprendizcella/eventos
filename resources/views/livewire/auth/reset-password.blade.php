<?php

use function Livewire\Volt\layout;

layout('layouts.auth');
?>

<form action="{{ route('password.reset.post') }}" method="POST">
    @csrf

    <input type="hidden" name="token" value="{{ request()->route('token') }}" />

    <h1 class="text-2xl font-semibold mb-6">Set New Password</h1>

    <x-form.field name="email" label="Email" type="email" :value="old('email', request()->string('email')->toString())" required autofocus />
    <x-form.password-input name="password" label="Password" required autocomplete="new-password" />
    <x-form.password-input name="password_confirmation" label="Confirm Password" required autocomplete="new-password" />

    <x-ui.button>Reset Password</x-ui.button>
</form>
