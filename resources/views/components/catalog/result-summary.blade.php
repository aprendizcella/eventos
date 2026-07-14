@props(['total' => 0, 'shown' => 0, 'hasSearch' => false, 'hasFilters' => false])

<div class="flex flex-wrap items-center justify-between gap-2">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @if($total > 0)
            {{ __('Showing') }}
            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $shown }}</span>
            {{ __('of') }}
            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $total }}</span>
            {{ __('events') }}
        @else
            {{ __('No events found') }}
        @endif
    </p>

    <div class="flex items-center gap-2">
        @if($hasSearch)
            <button type="button" wire:click="clearSearch" class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                {{ __('Clear search') }}
            </button>
        @endif

        @if($hasFilters)
            <button type="button" wire:click="resetFilters" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                {{ __('Reset filters') }}
            </button>
        @endif
    </div>
</div>
