<?php

declare(strict_types=1);

namespace App\ViewModels\Organizers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use App\ViewModels\ViewModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final class BillingReportsViewModel extends ViewModel
{
    private const string COUNT_INVOICE_RAW = 'COUNT(*) as invoice_count';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(public Organizer $organizer, private array $filters = []) {}

    /**
     * Income summary grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function incomeSummary(): Collection
    {
        return $this->baseQuery()
            ->select([
                'currency',
                DB::raw('SUM(amount) as total_income'),
                DB::raw('COALESCE(SUM(tax_amount), 0) as total_tax'),
                DB::raw('COALESCE(SUM(fee_amount), 0) as total_fees'),
                DB::raw(self::COUNT_INVOICE_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Tax totals grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function taxSummary(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('tax_amount')
            ->select([
                'currency',
                DB::raw('COALESCE(SUM(tax_amount), 0) as total_tax'),
                DB::raw(self::COUNT_INVOICE_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Platform fee totals grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function feeSummary(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('fee_amount')
            ->select([
                'currency',
                DB::raw('COALESCE(SUM(fee_amount), 0) as total_fees'),
                DB::raw(self::COUNT_INVOICE_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Recent invoices for the report table.
     *
     * @return Collection<int, Invoice>
     */
    public function recentInvoices(): Collection
    {
        $query = Invoice::query()
            ->where('organizer_id', $this->organizer->id)->latest()
            ->limit(50);

        $this->applyDateFilter($query, 'created_at');

        return $query->get();
    }

    /**
     * @return array<int, array{currency: string, total_income: int, total_tax: int, total_fees: int, invoice_count: int}>
     */
    public function csvRows(): array
    {
        return $this->incomeSummary()
            ->map(fn (object $row): array => [
                'currency' => $row->currency,
                'total_income' => $row->total_income,
                'total_tax' => $row->total_tax,
                'total_fees' => $row->total_fees,
                'invoice_count' => $row->invoice_count,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return QueryBuilder
     */
    private function baseQuery()
    {
        $query = DB::table('invoice')
            ->where('organizer_id', $this->organizer->id)
            ->where('type', InvoiceType::Invoice->value)
            ->whereIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Issued->value]);

        $this->applyDateFilter($query, 'created_at');

        return $query;
    }

    /**
     * @param  QueryBuilder|Builder<Invoice>  $query
     */
    private function applyDateFilter(QueryBuilder|Builder $query, string $column): void
    {
        $from = $this->filters['date_from'] ?? null;
        $to = $this->filters['date_to'] ?? null;

        if ($from instanceof CarbonInterface) {
            $query->where($column, '>=', $from->startOfDay());
        } elseif (is_string($from)) {
            $query->where($column, '>=', $from);
        }

        if ($to instanceof CarbonInterface) {
            $query->where($column, '<=', $to->endOfDay());
        } elseif (is_string($to)) {
            $query->where($column, '<=', $to);
        }
    }
}
