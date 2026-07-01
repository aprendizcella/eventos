<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Eventos') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <x-ui.theme-init />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100 min-h-screen flex flex-col">
        <header class="border-b border-gray-200 bg-white/85 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/85 sticky top-0 z-40">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🎟️</span>
                    <span class="font-bold text-lg text-gray-900 dark:text-white">{{ config('app.name', 'Eventos') }}</span>
                </div>
                <x-ui.theme-toggle />
            </div>
        </header>

        <main class="flex-1 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
            @yield('content', $slot ?? '')
        </main>

        <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400 dark:border-gray-800 dark:text-gray-600 bg-white dark:bg-gray-950">
            &copy; {{ date('Y') }} {{ config('app.name', 'Eventos') }}. {{ __('All rights reserved.') }}
        </footer>
        @livewireScripts
    </body>
</html>
