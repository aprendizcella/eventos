@extends('layouts.app')
@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('organizers.events.index', $organizer) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $event->title }}</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $organizer->name }} — Event Detail
            </p>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Detail Card --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">
                        @php
                            $statusClasses = match($event->status->value) {
                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'paused' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                            {{ ucfirst($event->status->value) }}
                        </span>
                    </dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Visibility</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ ucfirst($event->visibility->value) }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->slug }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Starts</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->starts_at?->format('M d, Y H:i') ?? '—' }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ends</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->ends_at?->format('M d, Y H:i') ?? '—' }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->category?->name ?? '—' }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Venue</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->venue?->name ?? '—' }}</dd>
                </div>

                @if ($event->description)
                    <div class="px-6 py-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                        <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100 prose prose-sm max-w-none dark:prose-invert">
                            {!! $event->description !!}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            @can('update', $event)
                <a href="{{ route('organizers.events.edit', [$organizer, $event]) }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Edit
                </a>
            @endcan

            @can('publish', $event)
                @if (in_array($event->status, [\App\Enums\EventStatus::Draft, \App\Enums\EventStatus::Configured, \App\Enums\EventStatus::Paused], true))
                    <form action="{{ route('organizers.events.publish', [$organizer, $event]) }}" method="POST"
                          onsubmit="return confirm('Publish this event?');">
                        @csrf
                        <button type="submit" name="publish"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            Publish
                        </button>
                    </form>
                @endif
            @endcan

            @can('pause', $event)
                @if ($event->status === \App\Enums\EventStatus::Published)
                    <form action="{{ route('organizers.events.pause', [$organizer, $event]) }}" method="POST"
                          onsubmit="return confirm('Pause this event?');">
                        @csrf
                        <button type="submit" name="pause"
                                class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            Pause
                        </button>
                    </form>
                @endif
            @endcan

            @can('cancel', $event)
                @if (! in_array($event->status->value, [\App\Enums\EventStatus::Cancelled->value, \App\Enums\EventStatus::Completed->value], true))
                    <form action="{{ route('organizers.events.cancel', [$organizer, $event]) }}" method="POST"
                          onsubmit="return confirm('Cancel this event? This action cannot be undone.');">
                        @csrf
                        <button type="submit" name="cancel"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            Cancel
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
@endsection
