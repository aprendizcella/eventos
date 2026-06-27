@php
    use App\Support\Organizers\OrganizerRoles;
@endphp

@extends('layouts.app')
@section('content')
    <div class="space-y-6" x-data="{
        showModal: false,
        showRoleModal: false,
        selectedUser: null,
        selectedRole: null,
        resetForm() {
            this.selectedUser = null;
            this.selectedRole = null;
        }
    }">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('organizers.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $organizer->name }} — Team
                    </h1>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage team members and their roles for this organizer.
                </p>
            </div>

            @can('manageTeam', $organizer)
                <button type="button" @click="showModal = true; resetForm()"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="mr-2 size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Add Member
                </button>
            @endcan
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Team Members Table --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Role
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Joined
                        </th>
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
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $member->pivot->created_at?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                @can('manageTeam', $organizer)
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="selectedUser = {{ $member->id }}; selectedRole = '{{ $member->pivot->role }}'; showRoleModal = true"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            Change Role
                                        </button>
                                        <form action="{{ route('organizers.team.destroy', [$organizer, $member]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this member?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No team members yet.
                                @can('manageTeam', $organizer)
                                    <button type="button" @click="showModal = true" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                        Add the first member
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Add Member Modal --}}
        @can('manageTeam', $organizer)
            <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="add-member-title" style="display: none;">
                <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.outside="showModal = false" @keydown.escape.window="showModal = false">
                    <form action="{{ route('organizers.team.store', $organizer) }}" method="POST">
                        @csrf
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 id="add-member-title" class="text-lg font-medium text-gray-900 dark:text-gray-100">Add Team Member</h3>
                        </div>
                        <div class="space-y-4 px-6 py-4">
                            <div>
                                <x-form.select
                                    name="user_id"
                                    label="User"
                                    :options="\App\Models\User::query()->whereNotIn('id', $members->pluck('id'))->get()->pluck('name', 'id')->map(fn ($name, $id) => $name)->prepend('Select a user...', '')->toArray()"
                                    :selected="old('user_id')"
                                    placeholder="Select a user..."
                                    required
                                />
                            </div>

                            <div>
                                <x-form.select
                                    name="role"
                                    label="Role"
                                    :options="OrganizerRoles::selectOptions()"
                                    :selected="old('role')"
                                    required
                                />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            <button type="button" @click="showModal = false"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <x-ui.button type="submit">Add Member</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Change Role Modal --}}
            <div x-show="showRoleModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="change-role-title" style="display: none;">
                <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800" @click.outside="showRoleModal = false" @keydown.escape.window="showRoleModal = false">
                    <template x-if="selectedUser">
                        <form :action="`{{ route('organizers.team.update', [$organizer, ':user']) }}`.replace(':user', selectedUser)" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="user_id" :value="selectedUser">
                            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                                <h3 id="change-role-title" class="text-lg font-medium text-gray-900 dark:text-gray-100">Change Role</h3>
                            </div>
                            <div class="space-y-4 px-6 py-4">
                                <div>
                                    <x-form.select
                                        name="role"
                                        label="New Role"
                                        :options="OrganizerRoles::selectOptions()"
                                        x-model="selectedRole"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                                <button type="button" @click="showRoleModal = false"
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    Cancel
                                </button>
                                <x-ui.button type="submit">Update Role</x-ui.button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        @endcan
    </div>
@endsection
