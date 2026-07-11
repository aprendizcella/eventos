<?php

declare(strict_types=1);

use App\DataTransferObjects\Reports\ReportAggregation;
use App\Models\Organizer;
use App\Services\Reports\ReportAggregationService;
use App\Support\Reports\CsvHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $currency = '';

    public string $selectedOrganizerId = '';

    public function mount(): void
    {
        // Default to last 90 days
        $this->dateFrom = now()->subDays(90)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function filter(): void
    {
        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'currency' => ['nullable', 'string', 'max:3'],
            'selectedOrganizerId' => ['nullable', 'string', 'max:10'],
        ]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $organizerAggs = $this->getOrganizerAggregations();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="platform-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($organizerAggs): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'Organizer',
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

            foreach ($organizerAggs as $row) {
                fputcsv($handle, CsvHelper::sanitizeRow([
                    $row->organizer_name,
                    $row->currency,
                    (string) $row->total_revenue,
                    (string) $row->total_tax,
                    (string) $row->total_fees,
                    (string) $row->invoice_count,
                    (string) $row->total_gross,
                    (string) $row->total_commission,
                    (string) $row->total_net,
                    (string) $row->payout_count,
                ]));
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'platform-report-' . now()->format('Y-m-d') . '.csv', $headers);
    }

    public function with(): array
    {
        return [
            'aggregations' => $this->getAggregations(),
            'organizerAggs' => $this->getOrganizerAggregations(),
            'organizers' => $this->getOrganizers(),
            'hasData' => $this->getOrganizerAggregations()->isNotEmpty(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\DataTransferObjects\Reports\ReportAggregation>
     */
    private function getAggregations(): Collection
    {
        $filter = new \App\DataTransferObjects\Reports\ReportFilterDto(
            dateFrom: $this->dateFrom !== '' ? \Carbon\Carbon::parse($this->dateFrom)->startOfDay() : null,
            dateTo: $this->dateTo !== '' ? \Carbon\Carbon::parse($this->dateTo)->endOfDay() : null,
            currency: $this->currency !== '' ? $this->currency : null,
            organizerId: $this->selectedOrganizerId !== '' ? (int) $this->selectedOrganizerId : null,
        );

        $service = new ReportAggregationService;

        return $service->aggregate($filter);
    }

    /**
     * @return Collection<int, object>
     */
    private function getOrganizerAggregations(): Collection
    {
        $dateFrom = $this->dateFrom !== '' ? \Carbon\Carbon::parse($this->dateFrom)->startOfDay() : null;
        $dateTo = $this->dateTo !== '' ? \Carbon\Carbon::parse($this->dateTo)->endOfDay() : null;
        $currency = $this->currency !== '' ? $this->currency : null;
        $organizerId = $this->selectedOrganizerId !== '' ? (int) $this->selectedOrganizerId : null;

        // Query invoice aggregates per organizer+currency
        $invoiceQuery = DB::table('invoice')
            ->select([
                'organizer_id',
                'currency',
                DB::raw('COALESCE(SUM(amount), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(tax_amount), 0) as total_tax'),
                DB::raw('COALESCE(SUM(fee_amount), 0) as total_fees'),
                DB::raw('COUNT(*) as invoice_count'),
            ])
            ->whereNull('deleted_at');

        // Query payout aggregates per organizer+currency
        $payoutQuery = DB::table('payout')
            ->select([
                'organizer_id',
                'currency',
                DB::raw('COALESCE(SUM(gross_amount), 0) as total_gross'),
                DB::raw('COALESCE(SUM(commission_amount), 0) as total_commission'),
                DB::raw('COALESCE(SUM(net_amount), 0) as total_net'),
                DB::raw('COUNT(*) as payout_count'),
            ])
            ->whereNull('deleted_at');

        // Apply filters
        if ($organizerId !== null) {
            $invoiceQuery->where('organizer_id', $organizerId);
            $payoutQuery->where('organizer_id', $organizerId);
        }

        if ($dateFrom !== null) {
            $invoiceQuery->where('created_at', '>=', $dateFrom);
            $payoutQuery->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $invoiceQuery->where('created_at', '<=', $dateTo);
            $payoutQuery->where('created_at', '<=', $dateTo);
        }

        if ($currency !== null) {
            $invoiceQuery->where('currency', $currency);
            $payoutQuery->where('currency', $currency);
        }

        $invoiceAggs = $invoiceQuery->groupBy('organizer_id', 'currency')->get()->keyBy(fn ($row) => $row->organizer_id.'-'.$row->currency);
        $payoutAggs = $payoutQuery->groupBy('organizer_id', 'currency')->get()->keyBy(fn ($row) => $row->organizer_id.'-'.$row->currency);

        // Merge by (organizer_id, currency) key
        $keys = $invoiceAggs->keys()->merge($payoutAggs->keys())->unique()->sort()->values();

        /** @var Collection<int, object> */
        return $keys->map(function (string $key) use ($invoiceAggs, $payoutAggs): object {
            $inv = $invoiceAggs->get($key);
            $pay = $payoutAggs->get($key);

            // Resolve organizer name from either side
            $organizer = Organizer::query()->find($inv->organizer_id ?? $pay->organizer_id);

            return (object) [
                'organizer_id' => $inv->organizer_id ?? $pay->organizer_id,
                'organizer_name' => $organizer?->name ?? 'Unknown',
                'currency' => $inv->currency ?? $pay->currency,
                'total_revenue' => (int) ($inv->total_revenue ?? 0),
                'total_tax' => (int) ($inv->total_tax ?? 0),
                'total_fees' => (int) ($inv->total_fees ?? 0),
                'invoice_count' => (int) ($inv->invoice_count ?? 0),
                'total_gross' => (int) ($pay->total_gross ?? 0),
                'total_commission' => (int) ($pay->total_commission ?? 0),
                'total_net' => (int) ($pay->total_net ?? 0),
                'payout_count' => (int) ($pay->payout_count ?? 0),
            ];
        })->sortByDesc('total_revenue')->values();
    }

    /**
     * @return Collection<int, Organizer>
     */
    private function getOrganizers(): Collection
    {
        /** @var Collection<int, Organizer> */
        return Organizer::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, object{currency: string, revenue: int, tax: int, fees: int, gross: int, commission: int, net: int}>
     */
    private function totalsByCurrency(): Collection
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
                {{ __('Platform Report Center') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Cross-organizer revenue, tax, fee and payout summaries for the platform.') }}
            </p>
        </div>
        @if ($hasData)
            <button wire:click="exportCsv" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none cursor-pointer">
                <svg class="mr-2 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ __('Export CSV') }}
            </button>
        @endif
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
            <div class="w-48 -mb-4">
                <x-form.select id="selectedOrganizerId" name="selectedOrganizerId" label="{{ __('Organizer') }}" wire:model="selectedOrganizerId" placeholder="{{ __('All Organizers') }}" :options="$organizers->pluck('name', 'id')->toArray()" />
            </div>
            <div class="pb-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Filter') }}
                </x-ui.button>
            </div>
        </form>
    </div>

    {{-- Per-Currency KPI Summary Cards --}}
    @if ($hasData)
        @php $currencies = $this->totalsByCurrency(); @endphp
        @foreach ($currencies as $tc)
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-800 dark:bg-gray-900/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Currency') }}: {{ $tc->currency }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($tc->revenue / 100, 2) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Tax') }}</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($tc->tax / 100, 2) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Fees') }}</p>
                    <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($tc->fees / 100, 2) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Gross') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($tc->gross / 100, 2) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Commission') }}</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($tc->commission / 100, 2) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Net') }}</p>
                    <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($tc->net / 100, 2) }}
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

    {{-- Per-Organizer Breakdown Table --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Organizer Breakdown') }}</h2>
        </div>
        <div class="p-6">
            @if ($hasData)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Organizer') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Currency') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Tax') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Fees') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Invoices') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Gross') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Commission') }}</th>
                                <th scope="col" class="px-4 py-3 text-right">{{ __('Net') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($organizerAggs as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row->organizer_name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row->currency }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($row->total_revenue / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($row->total_tax / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-red-600 dark:text-red-400">{{ number_format($row->total_fees / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->invoice_count }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($row->total_gross / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-amber-600 dark:text-amber-400">{{ number_format($row->total_commission / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-green-600 dark:text-green-400">{{ number_format($row->total_net / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No data found for the selected period.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
