<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Models\Event;
use App\ViewModels\Events\EventKpiViewModel;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;

    public function with(): array
    {
        $viewModel = new EventKpiViewModel($this->event);

        // Obtener historial de ventas netas de los últimos 30 días
        $salesHistory = $viewModel->salesHistory();

        // Formatear los puntos de coordenadas para el gráfico SVG
        $maxSales = max(array_values($salesHistory));
        if ($maxSales <= 0) {
            $maxSales = 100.0; // Evitar división por cero
        }

        $width = 600;
        $height = 120;
        $padding = 20;
        $baseline = $height - $padding;

        $totalDays = count($salesHistory) - 1;
        $pointsArray = [];
        $index = 0;

        foreach ($salesHistory as $amount) {
            $x = $padding + ($index / $totalDays) * ($width - 2 * $padding);
            $y = $baseline - ($amount / $maxSales) * ($height - 2 * $padding);

            $pointsArray[] = "{$x},{$y}";
            $index++;
        }

        $points = implode(' ', $pointsArray);
        $firstX = $padding;
        $lastX = $width - $padding;

        return [
            'kpis' => $viewModel->toArray(),
            'salesHistory' => $salesHistory,
            'svgPoints' => $points,
            'svgWidth' => $width,
            'svgHeight' => $height,
            'svgBaseline' => $baseline,
            'svgFirstX' => $firstX,
            'svgLastX' => $lastX,
            'maxSales' => $maxSales,
        ];
    }
}; ?>

<div class="space-y-6" wire:poll.30s>
    <!-- KPIs Card Grid -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        @foreach([
            'net_revenue' => ['label' => __('Net Revenue'), 'value' => number_format($kpis['net_revenue'], 2) . ' €', 'color' => 'green', 'icon' => 'M12 6v12m-3-2.818.752-.376a5.005 5.005 0 0 1 4.496 0l.752.376M3 5.625c0-1.036.84-1.875 1.875-1.875h14.25c1.036 0 1.875.84 1.875 1.875v12.75c0 1.036-.84 1.875-1.875 1.875H4.875A1.875 1.875 0 0 1 3 16.125V5.625Z'],
            'active_attendees_count' => ['label' => __('Tickets Sold'), 'value' => $kpis['active_attendees_count'], 'color' => 'indigo', 'icon' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H3.75a.75.75 0 0 1-.75-.75V6.75Z'],
            'check_in_rate' => ['label' => __('Check-In Rate'), 'value' => $kpis['check_in_rate'] . '%', 'color' => 'blue', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            'active_waitlist_count' => ['label' => __('Waitlist Requests'), 'value' => $kpis['active_waitlist_count'], 'color' => 'yellow', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ] as $kpi)
            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 border-l-4 border-l-{{ $kpi['color'] }}-500">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpi['value'] }}</dd>
                </div>
                <div class="text-{{ $kpi['color'] }}-500">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}" />
                    </svg>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Sales Overview Graph & Capacity -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- SVG Sales Chart -->
        <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">📈 {{ __('Daily Net Revenue (Last 30 Days)') }}</h3>

            <div class="relative flex min-h-[120px] w-full items-end rounded-lg bg-gray-50 p-2 dark:bg-gray-950/40" style="aspect-ratio: 5/1;">
                <svg class="h-full w-full" viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="sales-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#4F46E5" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#4F46E5" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path d="M {{ $svgFirstX }},{{ $svgBaseline }} L {{ $svgPoints }} L {{ $svgLastX }},{{ $svgBaseline }} Z" fill="url(#sales-gradient)" />
                    <polyline fill="none" stroke="#4F46E5" stroke-width="2" points="{{ $svgPoints }}" />
                </svg>
            </div>
            <div class="mt-2 flex justify-between px-1 text-xs text-gray-400">
                <span>{{ now()->subDays(29)->format('M d') }}</span>
                <span>{{ now()->subDays(15)->format('M d') }}</span>
                <span>{{ now()->format('M d') }}</span>
            </div>
        </div>

        <!-- Info & Capacity -->
        <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="mb-3 border-b border-gray-200 pb-3 text-base font-semibold text-gray-900 dark:text-white dark:border-gray-700">📊 {{ __('Capacity Status') }}</h3>
            <div class="space-y-4">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Capacity utilization') }}</span>
                    <span class="mt-1 block text-lg font-bold text-gray-900 dark:text-white">
                        {{ $kpis['capacity_utilization'] }}
                    </span>
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Event visibility') }}</span>
                    <span class="mt-1 block text-lg font-semibold capitalize text-gray-800 dark:text-gray-200">
                        {{ $event->visibility->value }}
                    </span>
                </div>
            </div>
            <div class="mt-4 border-t border-gray-200 pt-4 text-xs text-gray-400 dark:border-gray-700">
                {{ __('Auto-updates every 30 seconds.') }}
            </div>
        </div>
    </div>
</div>