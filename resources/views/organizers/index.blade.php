<x-layout.app>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Organizers</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage event organizers and their teams.
                </p>
            </div>

            @can('create', App\Models\Organizer::class)
                <a href="{{ route('organizers.create') }}"
                   class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="mr-2 size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Organizer
                </a>
            @endcan
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Organizers Table --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Slug
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Created
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($organizers as $organizer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="whitespace-nowrap px-6 py-4">
                                <a href="{{ route('organizers.show', $organizer) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400">
                                    {{ $organizer->name }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $organizer->slug }}
                            </td>
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
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $organizer->created_at->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('organizers.team.index', $organizer) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Team
                                    </a>
                                    @can('update', $organizer)
                                        <a href="{{ route('organizers.edit', $organizer) }}"
                                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $organizer)
                                        <form action="{{ route('organizers.destroy', $organizer) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this organizer?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        </form>
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

        {{-- Pagination --}}
        @if ($organizers->hasPages())
            <div class="flex justify-center">
                {{ $organizers->links() }}
            </div>
        @endif
    </div>
</x-layout.app>
