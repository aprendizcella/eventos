@props(['label', 'wireClick'])

<span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">
    {{ $label }}
    @if($wireClick)
        <button type="button" wire:click="{{ $wireClick }}" class="ml-0.5 inline-flex items-center rounded-full p-0.5 text-blue-500 hover:bg-blue-100 hover:text-blue-800 dark:hover:bg-blue-900/50 dark:hover:text-blue-300">
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
        </button>
    @endif
</span>
