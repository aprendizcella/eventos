<?php

declare(strict_types=1);

namespace App\Livewire\Public\Events;

use App\Models\Category;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Discovery\EventSearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] class extends Component {
    use WithPagination;

    // URL-synced state
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'cat', history: true)]
    public ?int $filterCategory = null;

    #[Url(as: 'city', history: true)]
    public ?string $filterCity = null;

    #[Url(as: 'date', history: true)]
    public ?string $filterDate = null;

    /** @var Collection<int, Category> */
    public ?Collection $categories = null;

    /** @var Collection<int, string> */
    public ?Collection $availableCities = null;

    protected EventSearchService $searchService;

    public function boot(EventSearchService $searchService): void
    {
        $this->searchService = $searchService;
    }

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
    public function events(): LengthAwarePaginator
    {
        // Build filters array
        $filters = [];

        $tenant = Organizer::current();
        if ($tenant !== null) {
            $filters['organizer_id'] = $tenant->id;
        }

        if ($this->filterCategory !== null) {
            $filters['category_id'] = $this->filterCategory;
        }

        if ($this->filterCity !== null && $this->filterCity !== '') {
            $filters['city'] = $this->filterCity;
        }

        if ($this->filterDate !== null && $this->filterDate !== '') {
            $filters['date'] = $this->filterDate;
        }

        // Delegate to search service which handles Scout/pagination:
        // - With text query: uses Scout/Meilisearch (relevance order preserved)
        // - Without text query: Eloquent WHERE LIKE fallback ordered by starts_at
        return $this->searchService->search(
            query: $this->search,
            filters: $filters,
            perPage: 12,
        );
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterCategory = null;
        $this->filterCity = null;
        $this->filterDate = null;
        $this->resetPage();
    }

    public function clearSearchAndFilters(): void
    {
        $this->search = '';
        $this->filterCategory = null;
        $this->filterCity = null;
        $this->filterDate = null;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCity(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    /**
     * Check if any filters or search are active.
     */
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->filterCategory !== null
            || ($this->filterCity !== null && $this->filterCity !== '')
            || ($this->filterDate !== null && $this->filterDate !== '');
    }
};

?>

<div>
    {{-- Public breadcrumb slot --}}
    @isset($breadcrumb)
        {{ $breadcrumb }}
    @endisset

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ __('Discover Events') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Browse upcoming events from our organizers.') }}
        </p>
    </div>

    {{-- Search bar --}}
    <div class="mb-6">
        <div class="relative">
            <x-catalog.search-bar model="search" placeholder="{{ __('Search events...') }}" />
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-8 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <x-catalog.filter-bar>
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
        </x-catalog.filter-bar>
    </div>

    {{-- Active filter chips --}}
    @if($this->hasActiveFilters())
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @if($search !== '')
                <x-catalog.filter-chip :label="__('Search: :query', ['query' => $search])" wireClick="clearSearch" />
            @endif
            @if($filterCategory !== null)
                @php $catName = $categories?->firstWhere('category_id', $filterCategory)?->name ?? ''; @endphp
                @if($catName)
                    <x-catalog.filter-chip :label="$catName" wireClick="resetFilters" />
                @endif
            @endif
            @if($filterCity !== null && $filterCity !== '')
                <x-catalog.filter-chip :label="$filterCity" wireClick="resetFilters" />
            @endif
        </div>
    @endif

    {{-- Results summary --}}
    <div class="mb-6">
        <x-catalog.result-summary
            :total="$this->events->total()"
            :shown="$this->events->count()"
            :hasSearch="$search !== ''"
            :hasFilters="$this->hasActiveFilters()"
        />
    </div>

    {{-- Loading skeleton --}}
    <div wire:loading class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @for($i = 0; $i < 6; $i++)
            <x-catalog.skeleton-card />
        @endfor
    </div>

    {{-- Event Grid --}}
    <div wire:loading.remove class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->events->items() as $event)
            <x-catalog.event-card :event="$event" />
        @empty
            <div class="col-span-full text-center py-16">
                <span class="text-5xl">🔍</span>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('No events found') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Try adjusting your search or filters.') }}
                </p>
                @if($this->hasActiveFilters())
                    <button type="button" wire:click="clearSearchAndFilters" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        {{ __('Clear all') }}
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($this->events->hasPages())
        <div class="mt-8">
            {{ $this->events->links() }}
        </div>
    @endif
</div>
