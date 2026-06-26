<div x-data="{ sidebarOpen: false }" class="flex min-h-screen bg-gray-50 dark:bg-gray-950">
    <x-navigation.sidebar />

    <div class="flex flex-1 flex-col">
        <x-navigation.topbar />

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>
