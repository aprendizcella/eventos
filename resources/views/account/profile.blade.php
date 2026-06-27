@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Profile</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage your account details and password.
                </p>
            </div>

            <a
                href="{{ route('account.password.edit') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
            >
                Change password
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @csrf
            @method('PUT')

            <x-form.input name="name" label="Name" :value="old('name', auth()->user()->name)" required autofocus />

            <x-form.input name="email" label="Email" :value="auth()->user()->email" disabled readonly />

            <x-ui.button>Update Profile</x-ui.button>
        </form>
    </div>
@endsection
