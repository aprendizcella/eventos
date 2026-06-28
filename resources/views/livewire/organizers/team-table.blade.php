<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use App\Actions\Organizers\AddTeamMemberAction;
use App\Actions\Organizers\ChangeTeamMemberRoleAction;
use App\Actions\Organizers\RemoveTeamMemberAction;
use App\DataTransferObjects\Organizers\AddTeamMemberDto;
use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use App\Exceptions\LastAdminCannotBeRemovedException;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Organizer $organizer;

    // Search
    public string $search = '';

    // Sorting and Pagination
    public string $sortField = 'joined_at';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    // Visible columns
    public array $visibleColumns = ['user', 'role', 'joined_at'];

    // Add Member form state
    public bool $showAddModal = false;
    public string $newUserId = '';
    public string $newRole = '';

    // Change Role form state
    public bool $showRoleModal = false;
    public ?int $selectedUserId = null;
    public string $selectedRole = '';

    // Error Alert Modal state
    public bool $showErrorModal = false;
    public string $errorMessage = '';

    // Delete Confirmation state
    public bool $showDeleteConfirmModal = false;
    public ?int $userIdToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'joined_at'],
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

    public function openAddModal(): void
    {
        $this->authorize('manageTeam', $this->organizer);

        $this->resetValidation();
        $this->reset(['newUserId', 'newRole']);
        $this->showAddModal = true;
    }

    public function storeMember(AddTeamMemberAction $action): void
    {
        $this->authorize('manageTeam', $this->organizer);

        $this->validate([
            'newUserId' => 'required|exists:users,id',
            'newRole' => 'required|string',
        ]);

        // Evitar duplicados
        if ($this->organizer->users()->where('users.id', $this->newUserId)->exists()) {
            $this->addError('newUserId', 'This user is already a member of the team.');
            return;
        }

        $dto = new AddTeamMemberDto((int) $this->newUserId, $this->newRole);

        $action($this->organizer, $dto, auth()->user());

        $this->showAddModal = false;
        session()->flash('success', 'Team member added successfully.');
    }

    public function openRoleModal(int $userId, string $role): void
    {
        $this->authorize('manageTeam', $this->organizer);

        $this->resetValidation();
        $this->selectedUserId = $userId;
        $this->selectedRole = $role;
        $this->showRoleModal = true;
    }

    public function updateRole(ChangeTeamMemberRoleAction $action): void
    {
        $this->authorize('manageTeam', $this->organizer);

        $this->validate([
            'selectedRole' => 'required|string',
        ]);

        $dto = new ChangeTeamMemberRoleDto((int) $this->selectedUserId, $this->selectedRole);

        try {
            $action($this->organizer, $dto, auth()->user());
            $this->showRoleModal = false;
            session()->flash('success', 'Member role updated successfully.');
        } catch (LastAdminCannotBeRemovedException $e) {
            $this->showRoleModal = false;
            $this->errorMessage = 'Cannot change the role of the last administrator. The organizer must have at least one administrator.';
            $this->showErrorModal = true;
        }
    }

    public function confirmRemoveMember(int $userId): void
    {
        $this->authorize('manageTeam', $this->organizer);

        $this->userIdToDelete = $userId;
        $this->showDeleteConfirmModal = true;
    }

    public function removeMember(RemoveTeamMemberAction $action): void
    {
        $this->authorize('manageTeam', $this->organizer);

        if (! $this->userIdToDelete) {
            return;
        }

        $userToRemove = User::findOrFail($this->userIdToDelete);

        try {
            $action($this->organizer, $userToRemove, auth()->user());
            session()->flash('success', 'Team member removed successfully.');
        } catch (LastAdminCannotBeRemovedException $e) {
            $this->errorMessage = 'Cannot remove the last administrator. The organizer must have at least one administrator.';
            $this->showErrorModal = true;
        } finally {
            $this->showDeleteConfirmModal = false;
            $this->userIdToDelete = null;
        }
    }

    public function downloadCsv(): void
    {
        $this->authorize('view', $this->organizer);

        $members = $this->organizer->users()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->get();

        $callback = function () use ($members): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Role', 'Joined At']);

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->name,
                    $member->email,
                    $member->pivot->role,
                    $member->pivot->created_at?->format('Y-m-d H:i') ?? '—',
                ]);
            }

            fclose($file);
        };

        $this->stream('team-' . $this->organizer->slug . '.csv', $callback, ['Content-Type' => 'text/csv']);
    }

    public function with(): array
    {
        $query = $this->organizer->users()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%'));

        // Mapper de ordenación
        if ($this->sortField === 'user') {
            $query->orderBy('name', $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } elseif ($this->sortField === 'role') {
            $query->orderBy('organizer_user.role', $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('organizer_user.created_at', $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }
        return [
            'members' => $query->paginate($this->perPage),
            'availableUsers' => User::query()
                ->whereNotIn('id', $this->organizer->users()->pluck('users.id'))
                ->get()
                ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
                ->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Success and Error messages --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

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
                            placeholder="Search name or email..."
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
                                    <input type="checkbox" wire:click="toggleColumn('user')" {{ $this->isColumnVisible('user') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    User
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('role')" {{ $this->isColumnVisible('role') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Role
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleColumn('joined_at')" {{ $this->isColumnVisible('joined_at') ? 'checked' : '' }} class="rounded text-blue-600" />
                                    Joined At
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

                    @can('manageTeam', $organizer)
                        <button type="button" wire:click="openAddModal"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none cursor-pointer">
                            <svg class="mr-1.5 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                            Add Member
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @if ($this->isColumnVisible('user'))
                            <th scope="col" wire:click="sortBy('user')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    User
                                    @if ($sortField === 'user')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('role'))
                            <th scope="col" wire:click="sortBy('role')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Role
                                    @if ($sortField === 'role')
                                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                            </th>
                        @endif
                        @if ($this->isColumnVisible('joined_at'))
                            <th scope="col" wire:click="sortBy('joined_at')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <div class="flex items-center gap-1.5">
                                    Joined
                                    @if ($sortField === 'joined_at')
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
                    @forelse ($members as $member)
                        @php
                            $memberRole = OrganizerRoles::tryFrom($member->pivot->role);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @if ($this->isColumnVisible('user'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-8 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                </td>
                            @endif
                            @if ($this->isColumnVisible('role'))
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($memberRole)
                                        @php
                                            $colors = $memberRole->badgeColors();
                                            $colorClass = $colors['bg'] . ' ' . $colors['text'] . ' ' . $colors['dark_bg'] . ' ' . $colors['dark_text'];
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorClass }}">
                                            {{ $memberRole->label() }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Unknown</span>
                                    @endif
                                </td>
                            @endif
                            @if ($this->isColumnVisible('joined_at'))
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $member->pivot->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                @can('manageTeam', $organizer)
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button"
                                                wire:click="openRoleModal({{ $member->id }}, '{{ $member->pivot->role }}')"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                                                title="Change Role"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="confirmRemoveMember({{ $member->id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 cursor-pointer"
                                                title="Remove Member"
                                        >
                                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No team members yet.
                                @can('manageTeam', $organizer)
                                    <button type="button" wire:click="openAddModal" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                        Add the first member
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($members->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of {{ $members->total() }} entries
                </div>
                <div>
                    {{ $members->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Add Member Modal --}}
    @can('manageTeam', $organizer)
        <x-ui.modal open="$wire.showAddModal" max-width="md">
            <x-slot:title>Add Team Member</x-slot:title>
            <form wire:submit="storeMember">
                <div class="space-y-4">
                    <x-form.select name="user_id" label="User" :options="$availableUsers" placeholder="Select a user" wire:model="newUserId" required />
                    <x-form.select name="role" label="Role" :options="OrganizerRoles::selectOptions()" placeholder="Select a role..." wire:model="newRole" required />
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="$wire.showAddModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                        Cancel
                    </button>
                    <x-ui.button type="submit">Add Member</x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        {{-- Change Role Modal --}}
        <x-ui.modal open="$wire.showRoleModal" max-width="md">
            <x-slot:title>Change Role</x-slot:title>
            <form wire:submit="updateRole">
                <div class="space-y-4">
                    <x-form.select name="role" label="New Role" :options="OrganizerRoles::selectOptions()" wire:model="selectedRole" required />
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="$wire.showRoleModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                        Cancel
                    </button>
                    <x-ui.button type="submit">Update Role</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endcan

    {{-- Error Alert Modal --}}
    <x-ui.modal open="$wire.showErrorModal" max-width="md">
        <x-slot:title>
            <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Action Denied
            </div>
        </x-slot:title>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $errorMessage }}
            </p>
        </div>
        <div class="mt-6 flex items-center justify-end">
            <button type="button" @click="$wire.showErrorModal = false"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                Understand
            </button>
        </div>
    </x-ui.modal>

    {{-- Delete Confirmation Modal --}}
    <x-ui.modal open="$wire.showDeleteConfirmModal" max-width="md">
        <x-slot:title>
            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Remove Team Member
            </div>
        </x-slot:title>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to remove this member from the team? This action cannot be undone.
            </p>
        </div>
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="$wire.showDeleteConfirmModal = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                Cancel
            </button>
            <button type="button" wire:click="removeMember"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none cursor-pointer">
                Remove
            </button>
        </div>
    </x-ui.modal>
</div>
