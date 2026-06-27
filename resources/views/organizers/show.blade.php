@extends('layouts.app')
@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $organizer->name }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Organizer details and team management.
                </p>
            </div>

            <div class="flex items-center gap-3">
                @can('update', $organizer)
                    <a href="{{ route('organizers.edit', $organizer) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900">
                        Edit
                    </a>
                @endcan
                <a href="{{ route('organizers.team.index', $organizer) }}"
                   class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    Manage Team
                </a>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Details Card --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Details</h2>
            </div>
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">{{ $organizer->name }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">{{ $organizer->slug }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Domain</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $organizer->domain ?? '—' }}
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="col-span-2">
                        @if ($organizer->status === 'active')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                Inactive
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $organizer->created_at->format('M d, Y \a\t H:i') }}
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Members</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">
                        {{ $organizer->users->count() }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Settings Card --}}
        @if ($organizer->settings)
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Settings</h2>
                </div>
                <div class="px-6 py-4">
                    <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">{{ json_encode($organizer->settings, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif

        {{-- Back Link --}}
        <div class="flex justify-start">
            <a href="{{ route('organizers.index') }}"
               class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                <svg class="mr-1 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to organizers
            </a>
        </div>
    </div>
@endsection
