<?php

declare(strict_types=1);

use App\Models\Organizer;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Search and filters
    public string $search = '';

    // Sorting and Pagination
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 10;

    // Visible columns
    public array $visibleColumns = ['name', 'slug', 'status', 'created_at'];

    // Delete Confirmation state
    public bool $showDeleteConfirmModal = false;
    public ?int $organizerIdToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
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
        $field = $this->sortFieldForQuery();

        $organizers = Organizer::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->get();

        $callback = function () use ($organizers): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Slug', 'Status', 'Created At']);

            foreach ($organizers as $org) {
                fputcsv($file, [
                    $org->name,
                    $org->slug,
                    $org->status,
                    $org->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        $this->stream('organizers.csv', $callback, ['Content-Type' => 'text/csv']);
    }

    public function confirmDelete(int $id): void
    {
        $this->organizerIdToDelete = $id;
        $this->showDeleteConfirmModal = true;
    }

    public function deleteOrganizer(): void
    {
        if (! $this->organizerIdToDelete) {
            return;
        }

        $organizer = Organizer::findOrFail($this->organizerIdToDelete);

        $this->authorize('delete', $organizer);

        $organizer->delete();

        session()->flash('success', 'Organizer deleted successfully.');

        $this->showDeleteConfirmModal = false;
        $this->organizerIdToDelete = null;
    }

    public function with(): array
    {
        $query = Organizer::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));

        $field = $this->sortFieldForQuery();
        $query->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc');

        return [
            'organizers' => $query->paginate($this->perPage),
        ];
    }

    protected function sortFieldForQuery(): string
    {
        $allowedFields = ['name', 'slug', 'status', 'created_at'];

        return in_array($this->sortField, $allowedFields, true) ? $this->sortField : 'created_at';
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
                                    <input type="checkbox" wire:click="toggleColumn('slug')" {{ $this->isColumnVisible('slug') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Slug
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('status')" {{ $this->isColumnVisible('status') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Status
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('created_at')" {{ $this->isColumnVisible('created_at') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Created At
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

                    @can('create', App\Models\Organizer::class)
                        <a href="{{ route('organizers.create') }}"
                           class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none">
                            <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Organizer
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
                        @if ($this->isColumnVisible('slug'))
                            <th scope="col" wire:click="sortBy('slug')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Slug
                                    @if ($sortField === 'slug')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('status'))
                            <th scope="col" wire:click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Status
                                    @if ($sortField === 'status')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('created_at'))
                            <th scope="col" wire:click="sortBy('created_at')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Created
                                    @if ($sortField === 'created_at')
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
                    @forelse ($organizers as $organizer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @if ($this->isColumnVisible('name'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    <a href="{{ route('organizers.show', $organizer) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400">
                                        {{ $organizer->name }}
                                    </a>
                                </td>
                            @endif
                            @if ($this->isColumnVisible('slug'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $organizer->slug }}
                                </td>
                            @endif
                            @if ($this->isColumnVisible('status'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($organizer->status === 'active')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                            @endif
                            @if ($this->isColumnVisible('created_at'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $organizer->created_at->format('M d, Y') }}
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('organizers.events.index', $organizer) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                                       title="Manage Events"
                                    >
                                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('organizers.venues.index', $organizer) }}"
                                       class="text-teal-600 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300 cursor-pointer"
                                       title="Manage Venues"
                                    >
                                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('organizers.team.index', $organizer) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 cursor-pointer"
                                       title="Manage Team"
                                    >
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </a>
                                    @can('update', $organizer)
                                        <a href="{{ route('organizers.edit', $organizer) }}"
                                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 cursor-pointer"
                                           title="Edit"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                    @endcan
                                    @can('delete', $organizer)
                                         <button type="button"
                                                 wire:click="confirmDelete({{ $organizer->id }})"
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
                                No organizers found.
                                @can('create', App\Models\Organizer::class)
                                    <a href="{{ route('organizers.create') }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                        Create your first organizer
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($organizers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $organizers->firstItem() }} to {{ $organizers->lastItem() }} of {{ $organizers->total() }} entries
                </div>
                <div>
                    {{ $organizers->links() }}
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
                Delete Organizer
            </div>
        </x-slot:title>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this organizer? All related events, venues and teams will be archived. This action cannot be undone.
            </p>
        </div>
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="$wire.showDeleteConfirmModal = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                Cancel
            </button>
            <button type="button" wire:click="deleteOrganizer"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                Delete
            </button>
        </div>
    </x-ui.modal>
</div>
