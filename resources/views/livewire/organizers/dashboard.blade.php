<?php

declare(strict_types=1);

use App\Models\Organizer;
use Livewire\Volt\Component;

new class extends Component {
    public Organizer $organizer;

    public function with(): array
    {
        return [
            'activeEventsCount' => $this->organizer->events()->where('status', \App\Enums\EventStatus::Published)->count(),
            'teamCount' => $this->organizer->users()->count(),
        ];
    }
};
?>

<div class="space-y-8">
    {{-- Top Heading --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ __('Overview') }} — {{ $organizer->name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Here is a summary of your organization\'s metrics and activities.') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('organizers.events.create', $organizer) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:hover:bg-blue-400">
                <svg class="mr-2 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Create Event') }}
            </a>
        </div>
    </div>

    {{-- Stats Cards Grid --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Sales (Placeholder de Fase 2) --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Sales') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">$0.00</p>
                </div>
            </div>
        </div>

        {{-- Card 2: Total Attendees (Placeholder de Fase 2/3) --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-blue-50 p-3 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Attendees') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                </div>
            </div>
        </div>

        {{-- Card 3: Active Events --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Active Events') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $activeEventsCount }}</p>
                </div>
            </div>
        </div>

        {{-- Card 4: Team Members --}}
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-4">
                <div class="rounded-lg bg-amber-50 p-3 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Team Members') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $teamCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Layout: Recent Orders & Quick Links --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Recent Orders Feed (Mockup styled based on "Pedidos recientes.png") --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('Recent Orders') }}
                </h3>
            </div>
            <div class="p-6">
                {{-- Mockup list --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Order') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Attendee') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Event') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                    #ORD-1003
                                </td>
                                <td class="px-4 py-3.5">
                                    <div>John Doe</div>
                                    <div class="text-xs text-gray-400">john@example.com</div>
                                </td>
                                <td class="px-4 py-3.5 max-w-xs truncate">
                                    Demo Event 2026
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950 dark:text-green-300">
                                        Paid
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-medium text-gray-900 dark:text-white">
                                    $49.00
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                    #ORD-1002
                                </td>
                                <td class="px-4 py-3.5">
                                    <div>Jane Smith</div>
                                    <div class="text-xs text-gray-400">jane@example.com</div>
                                </td>
                                <td class="px-4 py-3.5 max-w-xs truncate">
                                    Demo Event 2026
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950 dark:text-green-300">
                                        Paid
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-medium text-gray-900 dark:text-white">
                                    $0.00 (Free)
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-gray-900 dark:text-white">
                                    #ORD-1001
                                </td>
                                <td class="px-4 py-3.5">
                                    <div>Alice Cooper</div>
                                    <div class="text-xs text-gray-400">alice@example.com</div>
                                </td>
                                <td class="px-4 py-3.5 max-w-xs truncate">
                                    Tech Conference
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-950/30 dark:text-yellow-400">
                                        Pending
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-medium text-gray-900 dark:text-white">
                                    $120.00
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-xs text-gray-400 italic">
                    * {{ __('Orders shown above are mock data representing future Phase 2 (Checkout & Ticketing) integration.') }}
                </p>
            </div>
        </div>

        {{-- Quick Stats & Info --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('Platform Details') }}
            </h3>
            <div class="space-y-4">
                <div>
                    <h4 class="text-xs font-medium uppercase text-gray-400">{{ __('Domain Setup') }}</h4>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $organizer->domain ?? __('No custom domain set') }}
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-medium uppercase text-gray-400">{{ __('Organization Slug') }}</h4>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 font-mono">
                        {{ $organizer->slug }}
                    </p>
                </div>
                <div class="border-t border-gray-150 pt-4 dark:border-gray-800">
                    <h4 class="text-xs font-medium uppercase text-gray-400 mb-2">{{ __('Danger Zone Quick Link') }}</h4>
                    <a href="{{ route('organizers.settings', $organizer) }}#danger-zone" class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                        {{ __('Go to danger zone settings') }} &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
