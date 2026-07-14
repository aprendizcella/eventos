<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public Event $event;

    public function mount(Event $event): void
    {
        // Abort with 404 if the event is not public and published
        if ($event->visibility !== EventVisibility::Public || $event->status !== EventStatus::Published) {
            abort(404);
        }

        $this->event = $event->load(['organizer', 'venue', 'category']);
    }

    public function googleCalendarUrl(): string
    {
        $title = urlencode($this->event->title);
        $dates = '';

        if ($this->event->starts_at !== null) {
            $start = $this->event->starts_at->utc()->format('Ymd\THis\Z');
            $end = $this->event->ends_at?->utc()->format('Ymd\THis\Z')
                ?? $this->event->starts_at->addHours(2)->utc()->format('Ymd\THis\Z');
            $dates = "{$start}/{$end}";
        }

        $details = urlencode(Str::limit($this->event->description ?? '', 500));
        $location = urlencode($this->event->venue?->city ?? '');

        return "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$dates}&details={$details}&location={$location}";
    }

    public function appleCalendarUrl(): string
    {
        $title = urlencode($this->event->title);
        $location = urlencode($this->event->venue?->city ?? '');

        if ($this->event->starts_at !== null) {
            $start = $this->event->starts_at->utc()->format('Ymd\THis');
            $end = $this->event->ends_at?->utc()->format('Ymd\THis\Z')
                ?? $this->event->starts_at->addHours(2)->utc()->format('Ymd\THis');
            $dates = "{$start}/{$end}";
        } else {
            $dates = '';
        }

        // ICS data URI for Apple Calendar
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\n" .
            "DTSTART:{$start}\r\n" .
            "DTEND:{$end}\r\n" .
            "SUMMARY:{$title}\r\n" .
            "LOCATION:{$location}\r\n" .
            "DESCRIPTION:" . str_replace("\n", "\\n", $this->event->description ?? '') . "\r\n" .
            "END:VEVENT\r\nEND:VCALENDAR";

        return 'data:text/calendar;charset=utf-8,' . urlencode($ics);
    }
};

?>

<div>
    {{-- Breadcrumb via layout slot --}}
    <x-slot name="breadcrumb">
        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('public.events.catalog') }}" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                {{ __('Events') }}
            </a>
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[200px]">{{ $event->title }}</span>
        </nav>
    </x-slot>

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Title & Meta --}}
            <div>
                @if($event->category)
                    <span class="inline-block rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 mb-3">
                        {{ $event->category->name }}
                    </span>
                @endif

                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $event->title }}</h1>

                <div class="mt-4 space-y-2 text-sm text-gray-500 dark:text-gray-400">
                    @if($event->starts_at)
                        <div class="flex items-center gap-2">
                            <span>📅</span>
                            <span>{{ $event->starts_at->format('l, F d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>🕐</span>
                            <span>{{ $event->starts_at->format('g:i A') }}
                                @if($event->ends_at)
                                    - {{ $event->ends_at->format('g:i A') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($event->venue)
                        <div class="flex items-center gap-2">
                            <span>📍</span>
                            <span>
                                {{ $event->venue->name }}
                                @if($event->venue->city)
                                    , {{ $event->venue->city }}
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <span>🏢</span>
                        <span>{{ __('Organized by') }} <strong class="text-gray-700 dark:text-gray-300">{{ $event->organizer?->name }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            @if($event->description)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ __('About this Event') }}</h2>
                    <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-400">
                        {{ nl2br(e($event->description)) }}
                    </div>
                </div>
            @endif

            {{-- Calendar Actions --}}
            @if($event->starts_at)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('Add to Calendar') }}</h2>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ $this->googleCalendarUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            <span>📅</span> Google Calendar
                        </a>
                        <a href="{{ $this->appleCalendarUrl() }}" download="event.ics" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            <span>🍎</span> Apple Calendar
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Checkout CTA --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 text-center">
                <span class="text-4xl">🎟️</span>
                <h3 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">{{ __('Get Tickets') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Secure your spot for this event.') }}
                </p>
                <a href="{{ route('checkout', $event) }}" class="mt-4 inline-block w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-500 transition-colors text-center">
                    {{ __('Buy Tickets') }}
                </a>
            </div>

            {{-- Event Info Sidebar --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">{{ __('Event Info') }}</h3>

                <div class="space-y-3 text-sm">
                    @if($event->starts_at)
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Start') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $event->starts_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif

                    @if($event->ends_at)
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('End') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $event->ends_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif

                    @if($event->organizer)
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Organizer') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $event->organizer->name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
