@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Create Organizer</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Add a new event organizer to the platform.
            </p>
        </div>

        {{-- Form --}}
        <form action="{{ route('organizers.store') }}" method="POST" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @csrf

            <x-form.field name="name" label="Name" :value="old('name')" required autofocus />

            <x-form.field name="slug" label="Slug" :value="old('slug')" required>
                <x-slot:help>Unique identifier for URLs. Use lowercase letters, numbers, and hyphens.</x-slot:help>
            </x-form.field>

            <x-form.field name="domain" label="Domain" :value="old('domain')">
                <x-slot:help>Optional custom domain for this organizer (e.g., events.example.com).</x-slot:help>
            </x-form.field>

            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-gray-400 bg-white px-3 py-2 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                <a href="{{ route('organizers.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900">
                    Cancel
                </a>
                <x-ui.button type="submit">Create Organizer</x-ui.button>
            </div>
        </form>
    </div>
@endsection
