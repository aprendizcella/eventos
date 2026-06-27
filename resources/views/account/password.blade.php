@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-semibold mb-6">Change Password</h1>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('account.password.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-form.password-input name="current_password" label="Current Password" required autocomplete="current-password" />
            <x-form.password-input name="password" label="New Password" required autocomplete="new-password" />
            <x-form.password-input name="password_confirmation" label="Confirm New Password" required autocomplete="new-password" />

            <x-ui.button>Update Password</x-ui.button>
        </form>
    </div>
@endsection
