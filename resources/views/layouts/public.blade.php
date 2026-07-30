@php
    $githubUrl = 'https://github.com/aprendizcella/eventos';
    $brandName = config('app.name', 'Eventos');
    $navLinks = [
        [
            'href' => '/',
            'label' => __('Discover Events'),
            'aria_current' => request()->is('/') ? 'page' : null,
        ],
        [
            'href' => '/#features',
            'label' => __('Features'),
            'aria_current' => null,
        ],
        [
            'href' => '/docs',
            'label' => __('Docs'),
            'aria_current' => request()->is('docs') ? 'page' : null,
        ],
    ];

    $tfmLinks = [
        [
            'href' => '/tfm/slides',
            'label' => __('Slides'),
            'aria_current' => request()->is('tfm/slides') ? 'page' : null,
        ],
        [
            'href' => '/tfm/videos',
            'label' => __('Videos'),
            'aria_current' => request()->is('tfm/videos') ? 'page' : null,
        ],
    ];

    $isTfmPage = request()->is('tfm/slides') || request()->is('tfm/videos');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $brandName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @stack('seo')

        <x-ui.theme-init />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100 min-h-screen flex flex-col">
        <header class="border-b border-gray-200 bg-white/85 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/85 sticky top-0 z-40" x-data="{ open: false }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-2xl" aria-hidden="true">🎟️</span>
                    <a href="/" class="font-bold text-lg text-gray-900 dark:text-white" aria-label="{{ __('Go to discovery home') }}">
                        {{ $brandName }}
                    </a>
                </div>

                {{-- Desktop navigation --}}
                <nav aria-label="{{ __('Primary navigation') }}" class="hidden md:flex items-center gap-1">
                    @foreach ($navLinks as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @if (!empty($link['aria_current'])) aria-current="{{ $link['aria_current'] }}" @endif
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    {{-- TFM dropdown --}}
                    <div x-data="{ tfmOpen: false }" class="relative">
                        <button
                            type="button"
                            @click="tfmOpen = !tfmOpen"
                            @keydown.escape.window="tfmOpen = false"
                            :aria-expanded="tfmOpen.toString()"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            aria-haspopup="true"
                        >
                            <span>{{ __('TFM') }}</span>
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div
                            x-show="tfmOpen"
                            x-cloak
                            @click.outside="tfmOpen = false"
                            class="absolute right-0 mt-1 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            role="menu"
                        >
                            @foreach ($tfmLinks as $tfmLink)
                                <a
                                    href="{{ $tfmLink['href'] }}"
                                    @if (!empty($tfmLink['aria_current'])) aria-current="{{ $tfmLink['aria_current'] }}" @endif
                                    @click="tfmOpen = false"
                                    role="menuitem"
                                    class="block px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    {{ $tfmLink['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <a
                        href="{{ $githubUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="{{ __('GitHub (opens in a new tab)') }}"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                    >
                        <span aria-hidden="true">↗</span>
                        <span>GitHub</span>
                    </a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <span class="hidden sm:inline h-5 w-px bg-gray-200 dark:bg-gray-700"></span>

                    <x-ui.theme-toggle />

                    <span class="h-5 w-px bg-gray-200 dark:bg-gray-700"></span>

                    @auth
                        <a
                            href="{{ route('dashboard', absolute: false) }}"
                            aria-label="{{ __('Dashboard') }}"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-4 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200 hover:text-gray-900 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-white"
                            title="{{ __('Dashboard') }}"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('Dashboard') }}</span>
                        </a>
                    @else
                        <a
                            href="{{ route('login', absolute: false) }}"
                            aria-label="{{ __('Log in') }}"
                            title="{{ __('Log in') }}"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-3H9m0 0 3-3m-3 3 3 3" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('Log in') }}</span>
                        </a>
                    @endauth

                    {{-- Mobile menu control --}}
                    <button
                        type="button"
                        x-ref="mobileMenuButton"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="mobile-navigation"
                        aria-haspopup="true"
                        class="md:hidden inline-flex items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                        aria-label="{{ __('Toggle navigation menu') }}"
                    >
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Mobile navigation drawer --}}
            <nav
                id="mobile-navigation"
                x-show="open"
                x-cloak
                @click.outside="open = false"
                @keydown.escape.window="open = false; $refs.mobileMenuButton?.focus()"
                aria-label="{{ __('Mobile navigation') }}"
                class="md:hidden border-t border-gray-200 bg-white px-4 pb-4 pt-2 dark:border-gray-800 dark:bg-gray-900"
            >
                <ul class="space-y-1">
                    @foreach ($navLinks as $link)
                        <li>
                            <a
                                href="{{ $link['href'] }}"
                                @if (!empty($link['aria_current'])) aria-current="{{ $link['aria_current'] }}" @endif
                                class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach

                    {{-- TFM heading in mobile drawer --}}
                    <li class="pt-3">
                        <span class="block px-3 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('TFM') }}</span>
                    </li>
                    @foreach ($tfmLinks as $tfmLink)
                        <li>
                            <a
                                href="{{ $tfmLink['href'] }}"
                                @if (!empty($tfmLink['aria_current'])) aria-current="{{ $tfmLink['aria_current'] }}" @endif
                                class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                {{ $tfmLink['label'] }}
                            </a>
                        </li>
                    @endforeach

                    <li>
                        <a
                            href="{{ $githubUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('GitHub (opens in a new tab)') }}"
                            class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                        >
                            <span>GitHub</span>
                            <span aria-hidden="true">↗</span>
                        </a>
                    </li>
                    <li class="pt-2 border-t border-gray-100 dark:border-gray-800">
                        @auth
                            <a
                                href="{{ route('dashboard', absolute: false) }}"
                                class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                {{ __('Dashboard') }}
                            </a>
                        @else
                            <a
                                href="{{ route('login', absolute: false) }}"
                                class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                {{ __('Log in') }}
                            </a>
                        @endauth
                    </li>
                </ul>
            </nav>
        </header>

        <main class="flex-1 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
            {{-- Optional breadcrumb slot --}}
            @isset($breadcrumb)
                <div class="mb-6">
                    {{ $breadcrumb }}
                </div>
            @endisset

            @yield('content', $slot ?? '')
        </main>

        <footer class="border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                    <div class="max-w-sm">
                        <a href="/" class="inline-flex items-center gap-2">
                            <span class="text-2xl" aria-hidden="true">🎟️</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $brandName }}</span>
                        </a>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Discover events from participating organizers.') }}
                        </p>
                    </div>

                    <nav aria-label="{{ __('Footer navigation') }}" class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-4">
                        <a href="/" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('Discover Events') }}</a>
                        <a href="/#features" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('Features') }}</a>
                        <a href="/docs" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('Docs') }}</a>
                        <a href="{{ route('tfm.slides', absolute: false) }}" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('TFM Slides') }}</a>
                        <a href="{{ route('tfm.videos', absolute: false) }}" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('TFM Videos') }}</a>
                        <a
                            href="{{ $githubUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('GitHub (opens in a new tab)') }}"
                            class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span>GitHub</span>
                                <span aria-hidden="true">↗</span>
                            </span>
                        </a>
                        @auth
                            <a href="{{ route('dashboard', absolute: false) }}" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login', absolute: false) }}" class="text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ __('Log in') }}</a>
                        @endauth
                    </nav>
                </div>

                <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
                    &copy; {{ date('Y') }} {{ $brandName }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </footer>
        @livewireScripts
    </body>
</html>
