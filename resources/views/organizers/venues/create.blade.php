@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('organizers.venues.index', $organizer) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Create Venue</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $organizer->name }} — New Venue
            </p>
        </div>

        {{-- Form --}}
        <form action="{{ route('organizers.venues.store', $organizer) }}" method="POST" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @csrf

            <x-form.input name="name" label="Name" :value="old('name')" required autofocus />

            <x-form.input name="address" label="Address" :value="old('address')" required />

            <x-form.input name="city" label="City" :value="old('city')" />

            <x-form.input name="capacity" label="Capacity" :value="old('capacity')" type="number" />

            <x-form.textarea name="description" label="Description" :value="old('description')" :rows="4" />

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                <a href="{{ route('organizers.venues.index', $organizer) }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <x-ui.button type="submit">Create Venue</x-ui.button>
            </div>
        </form>
    </div>
@endsection
