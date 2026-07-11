<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Reports;

use App\Models\Organizer;
use App\Support\Reports\CsvHelper;
use App\ViewModels\Organizers\BillingReportsViewModel;
use Livewire\Volt\Component;

new class extends Component {
    public Organizer $organizer;

    public string $dateFrom = '';

    public string $dateTo = '';

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
        ]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewReports', $this->organizer);

        $viewModel = $this->getViewModel();
        $rows = $viewModel->csvRows();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="billing-report-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Currency', 'Total Income (cents)', 'Total Tax (cents)', 'Total Fees (cents)', 'Invoice Count']);

            foreach ($rows as $row) {
                fputcsv($handle, CsvHelper::sanitizeRow([
                    $row['currency'],
                    (string) $row['total_income'],
                    (string) $row['total_tax'],
                    (string) $row['total_fees'],
                    (string) $row['invoice_count'],
                ]));
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'billing-report-' . now()->format('Y-m-d') . '.csv', $headers);
    }

    public function with(): array
    {
        return [
            'viewModel' => $this->getViewModel(),
        ];
    }

    private function getViewModel(): BillingReportsViewModel
    {
        $filters = [];

        if ($this->dateFrom !== '') {
            $filters['date_from'] = $this->dateFrom;
        }

        if ($this->dateTo !== '') {
            $filters['date_to'] = $this->dateTo;
        }

        return new BillingReportsViewModel($this->organizer, $filters);
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                💰 {{ __('Billing Reports') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Income, tax, and platform fee summaries for :organizer.', ['organizer' => $organizer->name]) }}
            </p>
        </div>
        <button wire:click="exportCsv" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none cursor-pointer">
            <svg class="mr-2 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            {{ __('Export CSV') }}
        </button>
    </div>

    {{-- Date Filter --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form wire:submit="filter" class="flex flex-wrap items-end gap-4">
            <div class="w-40 -mb-4">
                <x-form.date id="dateFrom" name="dateFrom" label="{{ __('From') }}" wire:model="dateFrom" />
            </div>
            <div class="w-40 -mb-4">
                <x-form.date id="dateTo" name="dateTo" label="{{ __('To') }}" wire:model="dateTo" />
            </div>
            <div class="pb-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Filter') }}
                </x-ui.button>
            </div>
        </form>
    </div>

    {{-- Income Summary --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📈 {{ __('Income Summary') }}</h2>
        </div>
        <div class="p-6">
            @if ($viewModel->incomeSummary()->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No billing data found for the selected period.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Currency') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Total Income') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Total Tax') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Total Fees') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Invoices') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($viewModel->incomeSummary() as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row->currency }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($row->total_income / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($row->total_tax / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($row->total_fees / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right">{{ $row->invoice_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Tax Summary --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🧾 {{ __('Tax Summary') }}</h2>
        </div>
        <div class="p-6">
            @if ($viewModel->taxSummary()->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No tax data found for the selected period.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Currency') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Total Tax') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Invoices') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($viewModel->taxSummary() as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row->currency }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($row->total_tax / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right">{{ $row->invoice_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Platform Fee Summary --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📊 {{ __('Platform Fee Summary') }}</h2>
        </div>
        <div class="p-6">
            @if ($viewModel->feeSummary()->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No fee data found for the selected period.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Currency') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Total Fees') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Invoices') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($viewModel->feeSummary() as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row->currency }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($row->total_fees / 100, 2) }}</td>
                                    <td class="px-6 py-4 text-right">{{ $row->invoice_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Invoices --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📄 {{ __('Recent Invoices') }}</h2>
        </div>
        <div class="p-6">
            @if ($viewModel->recentInvoices()->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950/30">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No invoices found.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">{{ __('Invoice #') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Date') }}</th>
                                <th scope="col" class="px-6 py-3 text-right">{{ __('Amount') }}</th>
                                <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($viewModel->recentInvoices() as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-6 py-4 font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $invoice->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-right font-mono">{{ number_format($invoice->amount / 100, 2) }} {{ $invoice->currency }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                            {{ ucfirst($invoice->status->value) }}
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
