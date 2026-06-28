<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\Event;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Organizer $organizer;

    // Search and filters
    public string $search = '';
    public string $status = '';
    public string $visibility = '';
    public string $starts_from = '';
    public string $starts_until = '';

    // Sorting and Pagination
    public string $sortField = 'starts_at';
    public string $sortDirection = 'desc';
    public int $perPage = 10;

    // Visible columns
    public array $visibleColumns = ['title', 'status', 'visibility', 'starts_at'];

    // Delete Confirmation state
    public bool $showDeleteConfirmModal = false;
    public ?int $eventIdToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'visibility' => ['except' => ''],
        'starts_from' => ['except' => ''],
        'starts_until' => ['except' => ''],
        'sortField' => ['except' => 'starts_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingVisibility(): void
    {
        $this->resetPage();
    }

    public function updatingStartsFrom(): void
    {
        $this->resetPage();
    }

    public function updatingStartsUntil(): void
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
        $this->reset(['search', 'status', 'visibility', 'starts_from', 'starts_until', 'perPage']);
        $this->resetPage();
    }

    public function downloadCsv(): void
    {
        $this->authorize('viewAny', [Event::class, $this->organizer]);

        $field = $this->sortFieldForQuery();

        $events = $this->organizer->events()
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->visibility, fn ($q) => $q->where('visibility', $this->visibility))
            ->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->get();

        $callback = function () use ($events): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Title', 'Status', 'Visibility', 'Starts At']);

            foreach ($events as $event) {
                fputcsv($file, [
                    $event->title,
                    $event->status->value,
                    $event->visibility->value,
                    $event->starts_at?->format('Y-m-d H:i') ?? '—',
                ]);
            }

            fclose($file);
        };

        // Livewire download helper
        $this->stream(
            'events-' . $this->organizer->slug . '.csv',
            $callback,
            ['Content-Type' => 'text/csv']
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->eventIdToDelete = $id;
        $this->showDeleteConfirmModal = true;
    }

    public function deleteEvent(): void
    {
        if (! $this->eventIdToDelete) {
            return;
        }

        $event = $this->organizer->events()->whereKey($this->eventIdToDelete)->first();

        abort_unless($event instanceof Event, 404);

        $this->authorize('delete', $event);

        $event->delete();

        session()->flash('success', 'Event deleted successfully.');

        $this->showDeleteConfirmModal = false;
        $this->eventIdToDelete = null;
    }

    public function with(): array
    {
        $query = $this->organizer->events()
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->visibility, fn ($q) => $q->where('visibility', $this->visibility))
            ->when($this->starts_from, fn ($q) => $q->where('starts_at', '>=', $this->starts_from))
            ->when($this->starts_until, fn ($q) => $q->where('starts_at', '<=', $this->starts_until . ' 23:59:59'));

        $field = $this->sortFieldForQuery();
        $query->orderBy($field, $this->sortDirection === 'asc' ? 'asc' : 'desc');

        return [
            'events' => $query->paginate($this->perPage),
            'statusOptions' => ['' => 'All Statuses'] + collect(EventStatus::cases())
                ->mapWithKeys(fn (EventStatus $s) => [$s->value => ucfirst($s->value)])
                ->all(),
            'visibilityOptions' => ['' => 'All Visibilities'] + collect(EventVisibility::cases())
                ->mapWithKeys(fn (EventVisibility $v) => [$v->value => ucfirst($v->value)])
                ->all(),
        ];
    }

    protected function sortFieldForQuery(): string
    {
        $allowedFields = ['title', 'status', 'visibility', 'starts_at'];

        return in_array($this->sortField, $allowedFields, true) ? $this->sortField : 'starts_at';
    }
}; ?>

<div class="space-y-6">
    <div x-data="{ showFilters: false, showColumns: false }" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
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

                    {{-- Botón Filtros --}}
                    <button
                        type="button"
                        @click="showFilters = !showFilters"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                        Filters
                    </button>

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
                                    <input type="checkbox" wire:click="toggleColumn('title')" {{ $this->isColumnVisible('title') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Title
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('status')" {{ $this->isColumnVisible('status') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Status
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('visibility')" {{ $this->isColumnVisible('visibility') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Visibility
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('starts_at')" {{ $this->isColumnVisible('starts_at') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Starts At
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Botón Export / Download --}}
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

                    @can('create', [App\Models\Event::class, $organizer])
                        <a href="{{ route('organizers.events.create', $organizer) }}"
                           class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none">
                            <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create Event
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Collapsable Filters panel --}}
            <div
                x-show="showFilters"
                @click.away="showFilters = false"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="mt-4 p-4 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 rounded-xl"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="mb-0">
                        <x-form.select name="status" label="Status" :options="$statusOptions" wire:model.live="status" />
                    </div>
                    <div class="mb-0">
                        <x-form.select name="visibility" label="Visibility" :options="$visibilityOptions" wire:model.live="visibility" />
                    </div>
                    <div class="mb-0">
                        <x-form.date name="starts_from" label="From" wire:model.live="starts_from" />
                    </div>
                    <div class="mb-0">
                        <x-form.date name="starts_until" label="Until" wire:model.live="starts_until" />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @if ($this->isColumnVisible('title'))
                            <th scope="col" wire:click="sortBy('title')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Title
                                    @if ($sortField === 'title')
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
                        @if ($this->isColumnVisible('visibility'))
                            <th scope="col" wire:click="sortBy('visibility')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Visibility
                                    @if ($sortField === 'visibility')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('starts_at'))
                            <th scope="col" wire:click="sortBy('starts_at')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Starts At
                                    @if ($sortField === 'starts_at')
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
                    @forelse ($events as $event)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @if ($this->isColumnVisible('title'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    <a href="{{ route('organizers.events.show', [$organizer, $event]) }}"
                                       class="text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400">
                                        {{ $event->title }}
                                    </a>
                                </td>
                            @endif
                            @if ($this->isColumnVisible('status'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $statusClasses = match($event->status->value) {
                                            'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'paused' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                        {{ ucfirst($event->status->value) }}
                                    </span>
                                </td>
                            @endif
                            @if ($this->isColumnVisible('visibility'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ ucfirst($event->visibility->value) }}
                                </td>
                            @endif
                            @if ($this->isColumnVisible('starts_at'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $event->starts_at?->format('M d, Y H:i') ?? '—' }}
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    @can('update', $event)
                                        <a href="{{ route('organizers.events.edit', [$organizer, $event]) }}"
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                                           title="Edit"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                    @endcan
                                    @can('delete', $event)
                                        <button type="button"
                                                wire:click="confirmDelete({{ $event->event_id }})"
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
                                No events found.
                                @can('create', [App\Models\Event::class, $organizer])
                                    <a href="{{ route('organizers.events.create', $organizer) }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                        Create your first event
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($events->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} entries
                </div>
                <div>
                    {{ $events->links() }}
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
                Delete Event
            </div>
        </x-slot:title>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to delete this event? This action will put the event in the recycle bin (Soft Delete) and it will no longer be visible to visitors.
            </p>
        </div>
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="$wire.showDeleteConfirmModal = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                Cancel
            </button>
            <button type="button" wire:click="deleteEvent"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                Delete
            </button>
        </div>
    </x-ui.modal>
</div>
