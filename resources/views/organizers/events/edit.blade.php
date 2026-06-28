@extends('layouts.app')
@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('organizers.events.show', [$organizer, $event]) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Event</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $organizer->name }} — {{ $event->title }}
            </p>
        </div>

        {{-- Form --}}
        <form action="{{ route('organizers.events.update', [$organizer, $event]) }}" method="POST" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @csrf
            @method('PUT')

            <x-form.input name="title" label="Title" :value="old('title', $event->title)" required autofocus />

            <x-form.input name="slug" label="Slug" :value="old('slug', $event->slug)" required
                          help="Unique URL-friendly identifier. Use lowercase letters, numbers and hyphens." />

            <x-form.textarea name="description" label="Description" :value="old('description', $event->description)" :rows="6"
                             help="Safe HTML is preserved. Scripts and event handlers are stripped." />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.date name="starts_at" label="Starts at" :value="old('starts_at', $event->starts_at?->format('Y-m-d'))" />
                <x-form.date name="ends_at" label="Ends at" :value="old('ends_at', $event->ends_at?->format('Y-m-d'))" />
            </div>

            <x-form.select
                name="category_id"
                label="Category"
                :options="$categories->pluck('name', 'category_id')->prepend('— None —', '')->toArray()"
                :selected="old('category_id', $event->category_id)"
            />

            <x-form.select
                name="venue_id"
                label="Venue"
                :options="$venues->pluck('name', 'venue_id')->prepend('— None —', '')->toArray()"
                :selected="old('venue_id', $event->venue_id)"
            />

            <x-form.select
                name="visibility"
                label="Visibility"
                :options="$visibilityOptions"
                :selected="old('visibility', $event->visibility->value)"
            />

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                <a href="{{ route('organizers.events.show', [$organizer, $event]) }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <x-ui.button type="submit">Update Event</x-ui.button>
            </div>
        </form>
    </div>
@endsection
