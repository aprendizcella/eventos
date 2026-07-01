@extends('layouts.app')

@section('content')
    <div class="space-y-6" x-data="{ activeTab: 'overview' }">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('organizers.events.index', $organizer) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-500 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $event->title }}</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        🏢 {{ $organizer->name }} — {{ __('Event Management') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @php
                    $statusClasses = match($event->status->value) {
                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                        'published' => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-300 dark:border-green-800',
                        'paused' => 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900',
                        'cancelled' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-300 dark:border-red-900',
                        'completed' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-300 dark:border-blue-800',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                    {{ ucfirst($event->status->value) }}
                </span>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Sub-Navigation Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="-mb-px flex space-x-8" aria-label="Event tabs">
                <button
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
                >
                    📊 {{ __('Overview') }}
                </button>
                <button
                    @click="activeTab = 'tickets'"
                    :class="activeTab === 'tickets' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
                >
                    🎟️ {{ __('Tickets') }}
                </button>
                <button
                    @click="activeTab = 'attendees'"
                    :class="activeTab === 'attendees' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
                >
                    👥 {{ __('Attendees') }}
                </button>
                <button
                    @click="activeTab = 'actions'"
                    :class="activeTab === 'actions' ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium focus:outline-none cursor-pointer"
                >
                    ⚡ {{ __('Actions & Lifecycle') }}
                </button>
            </nav>
        </div>

        {{-- Tab Content: Overview --}}
        <div x-show="activeTab === 'overview'" class="space-y-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Event Information') }}</h2>
                </div>
                <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Visibility') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ ucfirst($event->visibility->value) }}</dd>
                    </div>

                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Slug') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 font-mono">{{ $event->slug }}</dd>
                    </div>

                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Starts At') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->starts_at?->format('M d, Y H:i') ?? '—' }}</dd>
                    </div>

                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Ends At') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->ends_at?->format('M d, Y H:i') ?? '—' }}</dd>
                    </div>

                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Category') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->category?->name ?? '—' }}</dd>
                    </div>

                    <div class="grid grid-cols-1 gap-2 px-6 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Venue / Location') }}</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 sm:col-span-2">{{ $event->venue?->name ?? '—' }}</dd>
                    </div>

                    @if ($event->description)
                        <div class="px-6 py-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Description') }}</dt>
                            <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100 prose prose-sm max-w-none dark:prose-invert">
                                {!! $event->description !!}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Tab Content: Tickets --}}
        <div x-show="activeTab === 'tickets'" class="space-y-4" x-cloak>
            <livewire:organizers.events.product-list :event="$event" />
        </div>

        {{-- Tab Content: Attendees (Phase 3 Placeholder) --}}
        <div x-show="activeTab === 'attendees'" class="space-y-4" x-cloak>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 text-center py-12">
                <span class="text-4xl">👥</span>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Attendee List & Check-in') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    {{ __('Accessing the guest list, custom form answers, and doing entry validation check-ins is part of Phase 3 (Operation).') }}
                </p>
                <div class="mt-6">
                    <button type="button" disabled class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-400 dark:bg-gray-800 dark:text-gray-500 cursor-not-allowed">
                        {{ __('View Guest List (Phase 3)') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Tab Content: Actions & Lifecycle --}}
        <div x-show="activeTab === 'actions'" class="space-y-6" x-cloak>
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Manage Event State') }}</h3>
                <div class="flex flex-wrap items-center gap-3">
                    @can('update', $event)
                        <a href="{{ route('organizers.events.edit', [$organizer, $event]) }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            {{ __('Edit Details') }}
                        </a>
                    @endcan

                    @can('publish', $event)
                        @if (in_array($event->status, [\App\Enums\EventStatus::Draft, \App\Enums\EventStatus::Configured, \App\Enums\EventStatus::Paused], true))
                            <form action="{{ route('organizers.events.publish', [$organizer, $event]) }}" method="POST"
                                  onsubmit="return confirm('Publish this event?');">
                                @csrf
                                <button type="submit" name="publish"
                                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none cursor-pointer">
                                    {{ __('Publish Event') }}
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
                                        class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-600 focus:outline-none cursor-pointer">
                                    {{ __('Pause Sales') }}
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
                                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                                    {{ __('Cancel Event') }}
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
