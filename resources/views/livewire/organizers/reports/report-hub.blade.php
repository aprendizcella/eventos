<?php

declare(strict_types=1);

use App\DataTransferObjects\Reports\ReportAggregation;
use App\Models\Event;
use App\Models\Organizer;
use App\Services\Reports\ReportAggregationService;
use App\Support\Reports\CsvHelper;
use Livewire\Volt\Component;

new class extends Component {
    public Organizer $organizer;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $currency = '';

    public function mount(): void
    {
        $this->authorize('viewReports', $this->organizer);

        // Default to last 90 days
        $this->dateFrom = now()->subDays(90)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function filter(): void
    {
        $this->authorize('viewReports', $this->organizer);

        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'currency' => ['nullable', 'string', 'max:3'],
        ]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewReports', $this->organizer);

        $aggregations = $this->getAggregations();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="organizer-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($aggregations): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'Currency',
                'Total Revenue (cents)',
                'Total Tax (cents)',
                'Total Fees (cents)',
                'Invoice Count',
                'Total Gross (cents)',
                'Total Commission (cents)',
                'Total Net (cents)',
                'Payout Count',
            ]);

            foreach ($aggregations as $agg) {
                fputcsv($handle, CsvHelper::sanitizeRow([
                    $agg->currency,
                    (string) $agg->totalRevenue,
                    (string) $agg->totalTax,
                    (string) $agg->totalFees,
                    (string) $agg->invoiceCount,
                    (string) $agg->totalGross,
                    (string) $agg->totalCommission,
                    (string) $agg->totalNet,
                    (string) $agg->payoutCount,
                ]));
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'organizer-report-' . now()->format('Y-m-d') . '.csv', $headers);
    }

    public function with(): array
    {
        return [
            'aggregations' => $this->getAggregations(),
            'events' => $this->getEvents(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\DataTransferObjects\Reports\ReportAggregation>
     */
    private function getAggregations(): \Illuminate\Support\Collection
    {
        $filter = new \App\DataTransferObjects\Reports\ReportFilterDto(
            dateFrom: $this->dateFrom !== '' ? \Carbon\Carbon::parse($this->dateFrom) : null,
            dateTo: $this->dateTo !== '' ? \Carbon\Carbon::parse($this->dateTo) : null,
            currency: $this->currency !== '' ? $this->currency : null,
            organizerId: $this->organizer->id,
        );

        $service = new ReportAggregationService;

        return $service->aggregate($filter);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function getEvents(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Support\Collection<int, Event> */
        return $this->organizer->events()
            ->select(['event_id', 'title', 'status', 'starts_at', 'ends_at'])
            ->orderByDesc('starts_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{currency: string, revenue: int, tax: int, fees: int, gross: int, commission: int, net: int}>
     */
    private function totalsByCurrency(): \Illuminate\Support\Collection
    {
        return $this->getAggregations()->map(fn (ReportAggregation $agg): object => (object) [
            'currency' => $agg->currency,
            'revenue' => $agg->totalRevenue,
            'tax' => $agg->totalTax,
            'fees' => $agg->totalFees,
            'gross' => $agg->totalGross,
            'commission' => $agg->totalCommission,
            'net' => $agg->totalNet,
        ]);
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ __('Report Center') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Revenue, tax, fee, payout and event performance summaries for :organizer.', ['organizer' => $organizer->name]) }}
            </p>
        </div>
        <button wire:click="exportCsv" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none cursor-pointer">
            <svg class="mr-2 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            {{ __('Export CSV') }}
        </button>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form wire:submit="filter" class="flex flex-wrap items-end gap-4">
            <div class="w-40 -mb-4">
                <x-form.date id="dateFrom" name="dateFrom" label="{{ __('From') }}" wire:model="dateFrom" />
            </div>
            <div class="w-40 -mb-4">
                <x-form.date id="dateTo" name="dateTo" label="{{ __('To') }}" wire:model="dateTo" />
            </div>
            <div class="w-48 -mb-4">
                <x-form.select id="currency" name="currency" label="{{ __('Currency') }}" wire:model="currency" placeholder="{{ __('All Currencies') }}" :options="['USD' => 'USD', 'EUR' => 'EUR', 'ARS' => 'ARS']" />
            </div>
            <div class="pb-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Filter') }}
                </x-ui.button>
            </div>
        </form>
    </div>

    @php
        $currencyTotals = $this->totalsByCurrency();
        $hasData = $currencyTotals->isNotEmpty();
    @endphp

    {{-- Per-Currency KPI Summary Cards --}}
    @if ($hasData)
        @foreach ($currencyTotals as $tc)
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-800 dark:bg-gray-900/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Currency') }}: {{ $tc->currency }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($tc->revenue / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('invoiceCount') }} {{ __('invoices') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Tax') }}</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($tc->tax / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('invoiceCount') }} {{ __('invoices') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Fees') }}</p>
                    <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($tc->fees / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('invoiceCount') }} {{ __('invoices') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Gross') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($tc->gross / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('payoutCount') }} {{ __('payouts') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Commission') }}</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($tc->commission / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('payoutCount') }} {{ __('payouts') }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Net') }}</p>
                    <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($tc->net / 100, 2) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $aggregations->where('currency', $tc->currency)->sum('payoutCount') }} {{ __('payouts') }}
                    </p>
                </div>
            </div>
        @endforeach
    @endif

    {{-- Contextual Warning Banner --}}
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 size-5 flex-shrink-0 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                    {{ __('This is an internal operational view. Actual settlement amounts may differ from the final Stripe payout.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Report Family Section Cards --}}
    <div class="grid gap-6 md:grid-cols-2">
        {{-- Revenue Section --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">💰 {{ __('Revenue') }}</h2>
            </div>
            <div class="p-6">
                @if ($hasData)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-4 py-3">{{ __('Currency') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Invoices') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($aggregations as $agg)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $agg->currency }}</td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format($agg->totalRevenue / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right">{{ $agg->invoiceCount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('organizers.reports.billing', $organizer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View detailed billing report') }} &rarr;
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No data found for the selected period.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Taxes Section --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🧾 {{ __('Taxes') }}</h2>
            </div>
            <div class="p-6">
                @if ($hasData)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-4 py-3">{{ __('Currency') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Tax') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Invoices') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($aggregations as $agg)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $agg->currency }}</td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format($agg->totalTax / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right">{{ $agg->invoiceCount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('organizers.reports.billing', $organizer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View detailed billing report') }} &rarr;
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No data found for the selected period.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Fees Section --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📊 {{ __('Fees') }}</h2>
            </div>
            <div class="p-6">
                @if ($hasData)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-4 py-3">{{ __('Currency') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Fees') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Invoices') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($aggregations as $agg)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $agg->currency }}</td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format($agg->totalFees / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right">{{ $agg->invoiceCount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('organizers.reports.billing', $organizer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View detailed billing report') }} &rarr;
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No data found for the selected period.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Payouts Section --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">💵 {{ __('Payouts') }}</h2>
            </div>
            <div class="p-6">
                @php
                    $hasPayouts = $aggregations->sum('payoutCount') > 0;
                @endphp
                @if ($hasPayouts)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="px-4 py-3">{{ __('Currency') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Gross') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Commission') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right">{{ __('Net') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($aggregations->where('payoutCount', '>', 0) as $agg)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $agg->currency }}</td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format($agg->totalGross / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-amber-600 dark:text-amber-400">{{ number_format($agg->totalCommission / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-green-600 dark:text-green-400">{{ number_format($agg->totalNet / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('organizers.reports.payouts', $organizer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            {{ __('View detailed payout report') }} &rarr;
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No payout data found for the selected period.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Event Performance Section (full width) --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📅 {{ __('Event Performance') }}</h2>
        </div>
        <div class="p-6">
            @if ($events->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Event') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Dates') }}</th>
                                <th scope="col" class="px-4 py-3 text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($events as $event)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $event->title }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                            {{ ucfirst($event->status->value ?? 'draft') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        @if ($event->starts_at)
                                            {{ $event->starts_at->format('M d, Y') }}
                                            @if ($event->ends_at)
                                                - {{ $event->ends_at->format('M d, Y') }}
                                            @endif
                                        @else
                                            {{ __('TBD') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('organizers.events.show', [$organizer, $event]) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                            {{ __('View') }} &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No events found for this organizer.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
