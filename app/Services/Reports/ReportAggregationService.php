<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DataTransferObjects\Reports\ReportAggregation;
use App\DataTransferObjects\Reports\ReportFilterDto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportAggregationService
{
    private const string RAW_INVOICE_SELECT = <<<'SQL'
        COALESCE(SUM(amount), 0) as total_revenue,
        COALESCE(SUM(tax_amount), 0) as total_tax,
        COALESCE(SUM(fee_amount), 0) as total_fees,
        COUNT(*) as invoice_count
    SQL;

    private const string RAW_PAYOUT_SELECT = <<<'SQL'
        COALESCE(SUM(gross_amount), 0) as total_gross,
        COALESCE(SUM(commission_amount), 0) as total_commission,
        COALESCE(SUM(net_amount), 0) as total_net,
        COUNT(*) as payout_count
    SQL;

    /**
     * Aggregate invoice and payout data grouped by currency.
     *
     * @return Collection<int, ReportAggregation>
     */
    public function aggregate(ReportFilterDto $filter): Collection
    {
        $invoiceAggs = $this->queryInvoiceAggregates($filter);
        $payoutAggs = $this->queryPayoutAggregates($filter);

        return $this->mergeAggregates($invoiceAggs, $payoutAggs);
    }

    /**
     * @return Collection<string, ReportAggregation>
     */
    private function queryInvoiceAggregates(ReportFilterDto $filter): Collection
    {
        $query = DB::table('invoice')
            ->select([
                'currency',
                DB::raw(self::RAW_INVOICE_SELECT),
            ])
            ->whereNull('deleted_at');

        $this->applyOrganizerFilter($query, $filter);
        $this->applyDateFilter($query, $filter);
        $this->applyCurrencyFilter($query, $filter);

        /** @var Collection<string, ReportAggregation> */
        return $query
            ->groupBy('currency')
            ->get()
            ->keyBy('currency')
            ->map(fn (object $row): ReportAggregation => new ReportAggregation(
                currency: $row->currency,
                totalRevenue: (int) $row->total_revenue,
                totalTax: (int) $row->total_tax,
                totalFees: (int) $row->total_fees,
                invoiceCount: (int) $row->invoice_count,
            ));
    }

    /**
     * @return Collection<string, ReportAggregation>
     */
    private function queryPayoutAggregates(ReportFilterDto $filter): Collection
    {
        $query = DB::table('payout')
            ->select([
                'currency',
                DB::raw(self::RAW_PAYOUT_SELECT),
            ])
            ->whereNull('deleted_at');

        $this->applyOrganizerFilter($query, $filter);
        $this->applyDateFilter($query, $filter);
        $this->applyCurrencyFilter($query, $filter);

        /** @var Collection<string, ReportAggregation> */
        return $query
            ->groupBy('currency')
            ->get()
            ->keyBy('currency')
            ->map(fn (object $row): ReportAggregation => new ReportAggregation(
                currency: $row->currency,
                totalGross: (int) $row->total_gross,
                totalCommission: (int) $row->total_commission,
                totalNet: (int) $row->total_net,
                payoutCount: (int) $row->payout_count,
            ));
    }

    /**
     * Merge invoice and payout aggregates by currency.
     *
     * @param  Collection<string, ReportAggregation>  $invoiceAggs
     * @param  Collection<string, ReportAggregation>  $payoutAggs
     * @return Collection<int, ReportAggregation>
     */
    private function mergeAggregates(Collection $invoiceAggs, Collection $payoutAggs): Collection
    {
        $currencies = $invoiceAggs->keys()
            ->merge($payoutAggs->keys())
            ->unique()
            ->sort()
            ->values();

        if ($currencies->isEmpty()) {
            return new Collection;
        }

        return $currencies->map(function (string $currency) use ($invoiceAggs, $payoutAggs): ReportAggregation {
            $invoice = $invoiceAggs->get($currency);
            $payout = $payoutAggs->get($currency);

            return new ReportAggregation(
                currency: $currency,
                totalRevenue: $invoice->totalRevenue ?? 0,
                totalTax: $invoice->totalTax ?? 0,
                totalFees: $invoice->totalFees ?? 0,
                invoiceCount: $invoice->invoiceCount ?? 0,
                totalGross: $payout->totalGross ?? 0,
                totalCommission: $payout->totalCommission ?? 0,
                totalNet: $payout->totalNet ?? 0,
                payoutCount: $payout->payoutCount ?? 0,
            );
        });
    }

    private function applyOrganizerFilter(Builder $query, ReportFilterDto $filter): void
    {
        if ($filter->organizerId !== null) {
            $query->where('organizer_id', $filter->organizerId);
        }
    }

    private function applyDateFilter(Builder $query, ReportFilterDto $filter): void
    {
        if ($filter->dateFrom instanceof \Carbon\CarbonInterface) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo instanceof \Carbon\CarbonInterface) {
            $query->where('created_at', '<=', $filter->dateTo);
        }
    }

    private function applyCurrencyFilter(Builder $query, ReportFilterDto $filter): void
    {
        if ($filter->currency !== null) {
            $query->where('currency', $filter->currency);
        }
    }
}
