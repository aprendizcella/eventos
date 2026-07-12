<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    // Filter state
    public ?int $filterCategory = null;
    public ?string $filterCity = null;
    public ?string $filterDate = null;

    /** @var Collection<int, Category> */
    public ?Collection $categories = null;

    /** @var Collection<int, string> */
    public ?Collection $availableCities = null;

    public function mount(): void
    {
        $this->categories = Category::query()->orderBy('name')->get();
        $this->availableCities = Venue::query()
            ->whereNotNull('city')
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->get()
            ->pluck('city');
    }

    #[Computed]
    public function events(): Collection
    {
        $query = Event::query()
            ->published()
            ->public()
            ->with(['organizer', 'venue', 'category']);

        // Tenant domain scoping
        $tenant = Organizer::current();
        if ($tenant !== null) {
            $query->where('organizer_id', $tenant->id);
        }

        // Apply filters
        if ($this->filterCategory !== null) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterCity !== null && $this->filterCity !== '') {
            $query->whereHas('venue', function ($q) {
                $q->where('city', $this->filterCity);
            });
        }

        if ($this->filterDate !== null && $this->filterDate !== '') {
            $query->whereDate('starts_at', $this->filterDate);
        }

        return $query->orderBy('starts_at')->get();
    }

    public function resetFilters(): void
    {
        $this->filterCategory = null;
        $this->filterCity = null;
        $this->filterDate = null;
    }
};

?>

<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ __('Discover Events') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Browse upcoming events from our organizers.') }}
        </p>
    </div>

    {{-- Filters --}}
    <div class="mb-8 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Category filter --}}
            <div class="w-full sm:w-48">
                <label for="filterCategory" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('Category') }}</label>
                <select id="filterCategory" wire:model.live="filterCategory" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->category_id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- City filter --}}
            <div class="w-full sm:w-48">
                <label for="filterCity" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('City') }}</label>
                <select id="filterCity" wire:model.live="filterCity" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ __('All Cities') }}</option>
                    @foreach($availableCities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date filter --}}
            <div class="w-full sm:w-48">
                <label for="filterDate" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('Date') }}</label>
                <input id="filterDate" type="date" wire:model.live="filterDate" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </div>

            {{-- Reset filters --}}
            <div>
                <button type="button" wire:click="resetFilters" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800">
                    {{ __('Reset') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Event Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->events as $event)
            <x-catalog.event-card :event="$event" />
        @empty
            <div class="col-span-full text-center py-16">
                <span class="text-5xl">🔍</span>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('No events found') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Try adjusting your filters or check back later for new events.') }}
                </p>
                @if($filterCategory !== null || ($filterCity !== null && $filterCity !== '') || ($filterDate !== null && $filterDate !== ''))
                    <button type="button" wire:click="resetFilters" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        {{ __('Reset Filters') }}
                    </button>
                @endif
            </div>
        @endforelse
    </div>
</div>
