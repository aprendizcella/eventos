<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\Venue;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Organizer $organizer;

    // Search and filters
    public string $search = '';

    // Sorting and Pagination
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    // Visible columns
    public array $visibleColumns = ['name', 'address', 'city', 'capacity'];

    // Delete Confirmation state
    public bool $showDeleteConfirmModal = false;
    public ?int $venueIdToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns = array_values(array_diff($this->visibleColumns, [$column]));
        } else {
            $this->visibleColumns[] = $column;
        }
    }

    public function isColumnVisible(string $column): bool
    {
        return in_array($column, $this->visibleColumns, true);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'perPage']);
        $this->resetPage();
    }

    public function downloadCsv(): void
    {
        $this->authorize('viewAny', [Venue::class, $this->organizer]);

        $field = $this->sortFieldForQuery();

        $venues = $this->organizer->venues()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->get();

        $callback = function () use ($venues): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Address', 'City', 'Capacity']);

            foreach ($venues as $v) {
                fputcsv($file, [
                    $v->name,
                    $v->address,
                    $v->city ?? '—',
                    $v->capacity ?? '—',
                ]);
            }

            fclose($file);
        };

        $this->stream('venues-' . $this->organizer->slug . '.csv', $callback, ['Content-Type' => 'text/csv']);
    }

    public function confirmDelete(int $id): void
    {
        $this->venueIdToDelete = $id;
        $this->showDeleteConfirmModal = true;
    }

    public function deleteVenue(): void
    {
        if (! $this->venueIdToDelete) {
            return;
        }

        $venue = $this->organizer->venues()->whereKey($this->venueIdToDelete)->first();

        abort_unless($venue instanceof Venue, 404);

        $this->authorize('delete', $venue);

        $venue->delete();

        session()->flash('success', 'Venue deleted successfully.');

        $this->showDeleteConfirmModal = false;
        $this->venueIdToDelete = null;
    }

    public function with(): array
    {
        $query = $this->organizer->venues()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        $field = $this->sortFieldForQuery();
        $query->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc');

        return [
            'venues' => $query->paginate($this->perPage),
        ];
    }

    protected function sortFieldForQuery(): string
    {
        $allowedFields = ['name', 'address', 'city', 'capacity'];

        return in_array($this->sortField, $allowedFields, true) ? $this->sortField : 'name';
    }
}; ?>

<div class="space-y-6">
    <div x-data="{ showColumns: false }" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        {{-- Toolbar --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                {{-- Show Entries / Left side --}}
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Show</span>
                    <select wire:model.live="perPage" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <span>entries</span>
                </div>

                {{-- Filters and Search / Right side --}}
                <div class="flex flex-wrap items-center gap-3 md:justify-end">
                    {{-- Búsqueda --}}
                    <div class="relative min-w-[200px] flex-1 sm:flex-none">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-1.5 pl-10 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-blue-500"
                        />
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 dark:text-gray-500">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Botón Columnas --}}
                    <div class="relative">
                        <button
                            type="button"
                            @click="showColumns = !showColumns"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 4.5v15m6-15v15m-10.5-3h15m-15-6h15m-15-6h15" />
                            </svg>
                            Columns
                        </button>
                        <div
                            x-show="showColumns"
                            @click.outside="showColumns = false"
                            x-cloak
                            class="absolute right-0 mt-2 z-10 w-48 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                        >
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 dark:text-gray-400">Columns visibility</span>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('name')" {{ $this->isColumnVisible('name') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Name
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('address')" {{ $this->isColumnVisible('address') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Address
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('city')" {{ $this->isColumnVisible('city') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    City
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('capacity')" {{ $this->isColumnVisible('capacity') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Capacity
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Botón Export --}}
                    <button
                        type="button"
                        wire:click="downloadCsv"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                        title="Download CSV"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download
                    </button>

                    @can('create', [App\Models\Venue::class, $organizer])
                        <a href="{{ route('organizers.venues.create', $organizer) }}"
                           class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none">
                            <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create Venue
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @if ($this->isColumnVisible('name'))
                            <th scope="col" wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Name
                                    @if ($sortField === 'name')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('address'))
                            <th scope="col" wire:click="sortBy('address')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Address
                                    @if ($sortField === 'address')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('city'))
                            <th scope="col" wire:click="sortBy('city')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    City
                                    @if ($sortField === 'city')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('capacity'))
                            <th scope="col" wire:click="sortBy('capacity')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Capacity
                                    @if ($sortField === 'capacity')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($venues as $venue)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @if ($this->isColumnVisible('name'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $venue->name }}
                                </td>
                            @endif
                            @if ($this->isColumnVisible('address'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $venue->address }}
                                </td>
                            @endif
                            @if ($this->isColumnVisible('city'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $venue->city ?? '—' }}
                                </td>
                            @endif
                            @if ($this->isColumnVisible('capacity'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $venue->capacity ?? '—' }}
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    @can('update', $venue)
                                        <a href="{{ route('organizers.venues.edit', [$organizer, $venue]) }}"
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                                           title="Edit"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                    @endcan
                                    @can('delete', $venue)
                                        <button type="button"
                                                wire:click="confirmDelete({{ $venue->venue_id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 cursor-pointer"
                                                title="Delete"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No venues found.
                                @can('create', [App\Models\Venue::class, $organizer])
                                    <a href="{{ route('organizers.venues.create', $organizer) }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                        Create your first venue
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($venues->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $venues->firstItem() }} to {{ $venues->lastItem() }} of {{ $venues->total() }} entries
                </div>
                <div>
                    {{ $venues->links() }}
                </div>
            </div>
        @endif
    {{-- Delete Confirmation Modal --}}
    <x-ui.modal open="$wire.showDeleteConfirmModal" max-width="md">
        <x-slot:title>
            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Delete Venue
            </div>
        </x-slot:title>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this venue? Any events scheduled at this venue may need to be updated. This action can be undone since it is a soft delete.
            </p>
        </div>
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="$wire.showDeleteConfirmModal = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                Cancel
            </button>
            <button type="button" wire:click="deleteVenue"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                Delete
            </button>
        </div>
    </x-ui.modal>
</div>
