<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Reports;

use App\Models\Organizer;
use App\Support\Reports\CsvHelper;
use App\ViewModels\Organizers\PayoutReportsViewModel;
use Livewire\Volt\Component;

new class extends Component {
    public Organizer $organizer;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewReports', $this->organizer);

        // Default to last 30 days
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function filter(): void
    {
        $this->authorize('viewReports', $this->organizer);

        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'status' => ['nullable', 'string', 'in:pending,ready,processed,reversed,failed'],
        ]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewReports', $this->organizer);

        $viewModel = $this->getViewModel();
        $rows = $viewModel->csvRows();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payout-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Date', 'Invoice #', 'Event', 'Gross (cents)', 'Commission (cents)', 'Net (cents)', 'Currency', 'Status']);

            foreach ($rows as $row) {
                fputcsv($handle, CsvHelper::sanitizeRow([
                    $row['date'],
                    $row['invoice_number'],
                    $row['event'],
                    (string) $row['gross_amount'],
                    (string) $row['commission_amount'],
                    (string) $row['net_amount'],
                    $row['currency'],
                    $row['status'],
                ]));
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'payout-report-' . now()->format('Y-m-d') . '.csv', $headers);
    }

    public function with(): array
    {
        return [
            'viewModel' => $this->getViewModel(),
        ];
    }

    private function getViewModel(): PayoutReportsViewModel
    {
        $filters = [];

        if ($this->dateFrom !== '') {
            $filters['date_from'] = $this->dateFrom;
        }

        if ($this->dateTo !== '') {
            $filters['date_to'] = $this->dateTo;
        }

        if ($this->status !== '') {
            $filters['status'] = $this->status;
        }

        return new PayoutReportsViewModel($this->organizer, $filters);
    }
}; ?>

<div class="space-y-6">
    {{-- Warning Banner --}}
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

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                💵 {{ __('Payout Reports') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Commission and payout summaries for :organizer.', ['organizer' => $organizer->name]) }}
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
                <x-form.select id="status" name="status" label="{{ __('Status') }}" wire:model="status" placeholder="{{ __('All Statuses') }}" :options="['pending' => __('Pending'), 'ready' => __('Ready'), 'processed' => __('Processed'), 'reversed' => __('Reversed'), 'failed' => __('Failed')]" />
            </div>
            <div class="pb-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Filter') }}
                </x-ui.button>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    @php
        $grossRow = $viewModel->totalGross()->first();
        $commissionRow = $viewModel->totalCommission()->first();
        $netRow = $viewModel->totalNet()->first();
    @endphp
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Gross') }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ $grossRow ? number_format($grossRow->total_gross / 100, 2) : '0.00' }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $grossRow ? $grossRow->currency : 'USD' }}
                &middot; {{ $grossRow ? $grossRow->payout_count : 0 }} {{ __('payouts') }}
            </p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Commission') }}</p>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                {{ $commissionRow ? number_format($commissionRow->total_commission / 100, 2) : '0.00' }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $commissionRow ? $commissionRow->currency : 'USD' }}
            </p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total Net') }}</p>
            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                {{ $netRow ? number_format($netRow->total_net / 100, 2) : '0.00' }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $netRow ? $netRow->currency : 'USD' }}
            </p>
        </div>
    </div>

    {{-- Payouts Table --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📄 {{ __('Recent Payouts') }}</h2>
        </div>
        <div class="p-6">
            @if ($viewModel->recentPayouts()->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No payouts found for the selected period.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Date') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Invoice #') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Event') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Gross') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Commission') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Net') }}</th>
                                <th scope="col" class="px-6 py-3 text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($viewModel->recentPayouts() as $payout)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $payout->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $payout->invoice?->invoice_number ?? '—' }}</td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $payout->invoice?->ticketOrder?->event?->title ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($payout->gross_amount / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-amber-600 dark:text-amber-400">{{ number_format($payout->commission_amount / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-green-600 dark:text-green-400">{{ number_format($payout->net_amount / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300',
                                                'ready' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
                                                'processed' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300',
                                                'reversed' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
                                                'failed' => 'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$payout->status->value] ?? $statusColors['pending'] }}">
                                            {{ ucfirst($payout->status->value) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
