@props(['event'])
@php
    /** @var \App\Models\Event $event */
    $hasStarted = $event->starts_at !== null && $event->starts_at->isPast();
    $hasEnded = $event->ends_at !== null && $event->ends_at->isPast();
@endphp

<a href="{{ route('public.events.detail', $event) }}" class="group block rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md hover:border-blue-300 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-blue-700 overflow-hidden">
    {{-- Date badge --}}
    @if($event->starts_at !== null)
        <div class="bg-blue-600 px-4 py-2 text-center text-white">
            <span class="block text-xs font-semibold uppercase tracking-wide">
                {{ $event->starts_at->format('M') }}
            </span>
            <span class="block text-2xl font-bold leading-tight">
                {{ $event->starts_at->format('d') }}
            </span>
        </div>
    @else
        <div class="bg-gray-100 px-4 py-3 text-center text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            {{ __('Date TBD') }}
        </div>
    @endif

    <div class="p-4 space-y-3">
        {{-- Category badge --}}
        @if($event->category)
            <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">
                {{ $event->category->name }}
            </span>
        @endif

        {{-- Title --}}
        <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
            {{ $event->title }}
        </h3>

        {{-- Details --}}
        <div class="space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
            @if($event->venue && $event->venue->city)
                <div class="flex items-center gap-1.5">
                    <span>📍</span>
                    <span>{{ $event->venue->city }}{{ $event->venue->name ? ', ' . $event->venue->name : '' }}</span>
                </div>
            @endif

            @if($event->starts_at)
                <div class="flex items-center gap-1.5">
                    <span>🕐</span>
                    <span>{{ $event->starts_at->format('g:i A') }}</span>
                </div>
            @endif
        </div>

        {{-- Organizer --}}
        @if($event->organizer)
            <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ __('by') }} <span class="font-medium text-gray-600 dark:text-gray-300">{{ $event->organizer->name }}</span>
                </span>
            </div>
        @endif
    </div>
</a>
