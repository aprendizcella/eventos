@props(['model' => 'search', 'placeholder' => __('Search events...')])

<div>
    <label for="catalog-search" class="sr-only">{{ $placeholder }}</label>
    <input
        id="catalog-search"
        type="search"
        wire:model.live.debounce.400ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
    />
    {{-- Search icon --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="size-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    </div>
</div>
