@props([
    'brand' => config('app.name', 'Eventos'),
])

@php
    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser?->hasRole('super_admin');
    
    // For super admins, we only show the organizer menu if we are explicitly on an organizer route context.
    // For regular tenant admins, we always show their organizer menu since they only manage that tenant.
    $sidebarOrganizer = null;
    if ($currentUser) {
        if ($isSuperAdmin) {
            $sidebarOrganizer = request()->route('organizer');
        } else {
            $sidebarOrganizer = request()->route('organizer') ?? $currentUser->currentOrganizer() ?? $currentUser->organizers()->orderBy('name')->first();
        }
    }
@endphp

<aside
    :class="sidebarOpen ? '' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-gray-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0 dark:border-gray-700 dark:bg-gray-900 flex flex-col h-screen"
    aria-label="Sidebar navigation"
>
    <!-- Brand -->
    <div class="flex h-16 items-center border-b border-gray-200 px-6 dark:border-gray-700 shrink-0">
        <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $brand }}
        </a>
    </div>

    <!-- Tenant Switcher / Selected Context -->
    @auth
        <div class="border-b border-gray-200 p-4 dark:border-gray-700 shrink-0">
            <livewire:organizers.tenant-switcher />
        </div>
    @endauth

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto flex flex-col gap-1 p-4" aria-label="Main navigation">
        <!-- Section: General -->
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
            @if(request()->routeIs('dashboard')) aria-current="page" @endif
        >
            <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            {{ __('Dashboard') }}
        </a>

        @if ($sidebarOrganizer)
            <!-- Section: Organizer context is active -->
            <span class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                {{ __('Organizer Menu') }}
            </span>

            <!-- Organizer Dashboard Link -->
            <a
                href="{{ route('organizers.dashboard', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.dashboard')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                {{ __('Overview') }}
            </a>

            <!-- Events Link -->
            <a
                href="{{ route('organizers.events.index', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.events.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.events.*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                {{ __('Events') }}
            </a>

            <!-- Team Link -->
            <a
                href="{{ route('organizers.team.index', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.team.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.team.*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                {{ __('Team') }}
            </a>

            <!-- Venues Link -->
            <a
                href="{{ route('organizers.venues.index', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.venues.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.venues.*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                {{ __('Venues') }}
            </a>

            <!-- Reports Link -->
            <a
                href="{{ route('organizers.reports.index', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.reports.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.reports.*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                {{ __('Reports') }}
            </a>

            <!-- Settings Link -->
            <a
                href="{{ route('organizers.settings', $sidebarOrganizer) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.settings') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.settings')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('Settings') }}
            </a>
        @endif

        @if ($isSuperAdmin)
            <!-- Section: Platform Administration -->
            <span class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                {{ __('Platform Administration') }}
            </span>

            <!-- Organizers (Global list) -->
            <a
                href="{{ route('organizers.index') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('organizers.index', 'organizers.create', 'organizers.show', 'organizers.edit') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('organizers.index', 'organizers.create', 'organizers.show', 'organizers.edit')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                {{ __('Organizers') }}
            </a>

            <!-- Platform Reports -->
            <a
                href="{{ route('admin.reports.index') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->routeIs('admin.reports.*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                {{ __('Platform Reports') }}
            </a>

            <!-- Horizon (Queue Monitoring) -->
            <a
                href="{{ url('/horizon') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->is('horizon*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}"
                @if(request()->is('horizon*')) aria-current="page" @endif
            >
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
                {{ __('Queue Monitor') }}
            </a>
        @endif
    </nav>
</aside>

{{-- Mobile overlay --}}
<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
    aria-hidden="true"
></div>
