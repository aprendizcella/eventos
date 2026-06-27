@php
    $accountContext = app(\App\Support\Account\AccountContextResolver::class)->resolve(auth()->user());
@endphp

<header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-900 sm:px-6">
    <div class="flex items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            :aria-expanded="sidebarOpen.toString()"
            class="inline-flex items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
            aria-label="Toggle sidebar"
        >
            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    <div class="flex items-center gap-2">
        <x-ui.theme-toggle />

        @auth
            <div class="relative" x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-2 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                    aria-label="User menu"
                    aria-expanded="false"
                    :aria-expanded="open.toString()"
                >
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    @click.outside="open = false"
                    x-cloak
                    class="absolute right-0 z-50 mt-2 w-64 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $accountContext->roleLabel }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $accountContext->organizerLabel }}</p>
                    </div>

                    <a
                        href="{{ route('account.profile.edit') }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Profile
                    </a>

                    <a
                        href="{{ route('account.password.edit') }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Change password
                    </a>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        @endauth

        @guest
            <div class="relative">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                    aria-label="User menu"
                >
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </button>
            </div>
        @endguest
    </div>
</header>
