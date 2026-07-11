<?php

declare(strict_types=1);

namespace App\ViewModels\Organizers;

use App\Models\Organizer;
use App\Models\Payout;
use App\ViewModels\ViewModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final class PayoutReportsViewModel extends ViewModel
{
    private const string COUNT_PAYOUT_RAW = 'COUNT(*) as payout_count';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(public Organizer $organizer, private array $filters = []) {}

    /**
     * Gross amounts grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function totalGross(): Collection
    {
        return $this->baseQuery()
            ->select([
                'currency',
                DB::raw('COALESCE(SUM(gross_amount), 0) as total_gross'),
                DB::raw(self::COUNT_PAYOUT_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Commission amounts grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function totalCommission(): Collection
    {
        return $this->baseQuery()
            ->select([
                'currency',
                DB::raw('COALESCE(SUM(commission_amount), 0) as total_commission'),
                DB::raw(self::COUNT_PAYOUT_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Net amounts grouped by currency.
     *
     * @return Collection<int, stdClass>
     */
    public function totalNet(): Collection
    {
        return $this->baseQuery()
            ->select([
                'currency',
                DB::raw('COALESCE(SUM(net_amount), 0) as total_net'),
                DB::raw(self::COUNT_PAYOUT_RAW),
            ])
            ->groupBy('currency')
            ->get();
    }

    /**
     * Recent payouts with eager loaded relations.
     *
     * @return Collection<int, Payout>
     */
    public function recentPayouts(): Collection
    {
        return $this->buildPayoutQuery()->limit(50)->get();
    }

    /**
     * All matching payouts for CSV export (unlimited).
     *
     * @return Collection<int, Payout>
     */
    public function allPayoutsExport(): Collection
    {
        return $this->buildPayoutQuery()->get();
    }

    /**
     * @return array<int, array{date: string, invoice_number: string, event: string, gross_amount: int, commission_amount: int, net_amount: int, currency: string, status: string}>
     */
    public function csvRows(): array
    {
        return $this->allPayoutsExport()
            ->map(fn (Payout $payout): array => [
                'date' => $payout->created_at?->format('Y-m-d') ?? '',
                'invoice_number' => $payout->invoice->invoice_number ?? '',
                'event' => $payout->invoice->ticketOrder->event->title ?? '',
                'gross_amount' => $payout->gross_amount,
                'commission_amount' => $payout->commission_amount,
                'net_amount' => $payout->net_amount,
                'currency' => $payout->currency,
                'status' => $payout->status->value,
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
     * @return Builder<Payout>
     */
    private function buildPayoutQuery(): Builder
    {
        $query = Payout::query()
            ->where('organizer_id', $this->organizer->id)
            ->with('invoice.ticketOrder.event')
            ->latest();

        $this->applyDateFilter($query, 'created_at');
        $this->applyStatusFilter($query);

        return $query;
    }

    /**
     * @return QueryBuilder
     */
    private function baseQuery()
    {
        $query = DB::table('payout')
            ->where('organizer_id', $this->organizer->id)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, 'created_at');
        $this->applyStatusFilter($query);

        return $query;
    }

    /**
     * @param  QueryBuilder|Builder<Payout>  $query
     */
    private function applyDateFilter(QueryBuilder|Builder $query, string $column): void
    {
        $from = $this->filters['date_from'] ?? null;
        $to = $this->filters['date_to'] ?? null;

        if ($from instanceof CarbonInterface) {
            $query->where($column, '>=', $from->startOfDay());
        } elseif (is_string($from) && $from !== '') {
            $query->where($column, '>=', \Carbon\Carbon::parse($from)->startOfDay());
        }

        if ($to instanceof CarbonInterface) {
            $query->where($column, '<=', $to->endOfDay());
        } elseif (is_string($to) && $to !== '') {
            $query->where($column, '<=', \Carbon\Carbon::parse($to)->endOfDay());
        }
    }

    /**
     * @param  QueryBuilder|Builder<Payout>  $query
     */
    private function applyStatusFilter(QueryBuilder|Builder $query): void
    {
        $status = $this->filters['status'] ?? null;

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
    }
}
