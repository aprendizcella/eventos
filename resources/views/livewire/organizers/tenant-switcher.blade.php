<?php

declare(strict_types=1);

use App\Models\Organizer;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function selectOrganizer(int $organizerId): void
    {
        $this->redirect(route('organizers.events.index', $organizerId));
    }

    public function with(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isGlobalSuperAdmin() ?? false;
        $currentOrganizer = request()->route('organizer') ?? $user?->currentOrganizer();

        if (!$user) {
            return [
                'organizers' => collect(),
                'currentOrganizer' => null,
                'isSuperAdmin' => false,
            ];
        }

        $query = $isSuperAdmin 
            ? Organizer::query()->orderBy('name')
            : $user->organizers()->orderBy('name');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return [
            'organizers' => $query->limit(8)->get(),
            'currentOrganizer' => $currentOrganizer,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }
};
?>

<div x-data="{ open: false }" class="relative w-full">
    @if ($isSuperAdmin || $organizers->count() > 1)
        <!-- Trigger Button -->
        <button
            @click="open = !open"
            type="button"
            class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/50 transition-colors"
            aria-haspopup="true"
            :aria-expanded="open.toString()"
        >
            <span class="truncate pr-2">
                @if ($currentOrganizer)
                    🏢 {{ $currentOrganizer->name }}
                @else
                    🌐 {{ __('Global / Superadmin') }}
                @endif
            </span>
            <svg class="size-4 shrink-0 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
            x-show="open"
            @click.outside="open = false"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute left-0 z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <!-- Search Input -->
            <div class="relative mb-2">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                    <svg class="size-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="{{ __('Search organizer...') }}"
                    class="block w-full rounded-md border border-gray-200 bg-white py-1.5 pl-8 pr-3 text-xs text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                    @click.stop
                >
            </div>

            <!-- Options List -->
            <div class="max-h-52 overflow-y-auto space-y-0.5">
                @if ($isSuperAdmin)
                    <a
                        href="{{ route('organizers.index') }}"
                        @click="open = false"
                        class="block rounded px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 {{ !$currentOrganizer ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 font-semibold' : '' }}"
                    >
                        🌐 {{ __('Global / Superadmin') }}
                    </a>
                    <div class="border-t border-gray-100 my-1 dark:border-gray-700"></div>
                @endif

                @forelse ($organizers as $org)
                    <button
                        type="button"
                        wire:click="selectOrganizer({{ $org->id }})"
                        @click="open = false"
                        class="flex w-full items-center rounded px-2.5 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 {{ $currentOrganizer?->id === $org->id ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 font-semibold' : '' }}"
                    >
                        <span class="truncate">🏢 {{ $org->name }}</span>
                    </button>
                @empty
                    <div class="px-2.5 py-2 text-xs text-gray-400 dark:text-gray-500 italic">
                        {{ __('No organizers found') }}
                    </div>
                @endforelse
            </div>
        </div>
    @elseif ($currentOrganizer)
        <!-- Static Name (Single Organizer Tenant Admin) -->
        <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <span class="shrink-0">🏢</span>
            <span class="truncate">{{ $currentOrganizer->name }}</span>
        </div>
    @endif
</div>
