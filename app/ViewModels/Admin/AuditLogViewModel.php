<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\DataTransferObjects\Admin\AuditLogFilterDto;
use App\Exceptions\AuditLogQueryException;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class AuditLogViewModel
{
    /**
     * Projects and paginates raw activities safely.
     *
     * @return LengthAwarePaginator<int, AuditLogEntryDto>
     */
    public function getLogs(AuditLogFilterDto $filter, int $perPage = 10): LengthAwarePaginator
    {
        // Enforce maximum bounded page size
        $boundedPerPage = min(max(1, $perPage), 50);

        try {
            if (!$filter->isSafe()) {
                throw new InvalidArgumentException('Unsafe audit log filter.');
            }

            $paginator = $this->queryActivities($filter)->paginate($boundedPerPage);
            $this->logExcludedActivities($paginator->items());

            return $this->mapPaginator($paginator);

        } catch (Throwable) {
            // Redact exception detail - only report generic error without leaking context or stack traces
            Log::error('Global audit query failed with database exception.', [
                'error' => 'Database query failure',
            ]);

            throw new AuditLogQueryException;
        }
    }

    /**
     * @return Builder<Activity>
     */
    private function queryActivities(AuditLogFilterDto $filter): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->select(['id', 'log_name', 'description', 'event', 'subject_id', 'subject_type', 'causer_id', 'causer_type', 'created_at', 'is_global', 'organizer_id'])
            ->whereNull('organizer_id')
            ->where('is_global', true)
            ->when($filter->logName !== null, fn (Builder $query): Builder => $query->where('log_name', $filter->logName))
            ->when($filter->event !== null, fn (Builder $query): Builder => $query->where('event', $filter->event))
            ->when($filter->dateFrom instanceof \Carbon\CarbonInterface, fn (Builder $query): Builder => $query->where('created_at', '>=', $filter->dateFrom))
            ->when($filter->dateTo instanceof \Carbon\CarbonInterface, fn (Builder $query): Builder => $query->where('created_at', '<=', $filter->dateTo))
            ->latest()
            ->orderBy('id', 'desc');
    }

    /**
     * @param  array<int, Activity>  $items
     */
    private function logExcludedActivities(array $items): void
    {
        if ($items === []) {
            return;
        }

        $maxId = $items[0]->id;
        $minId = $items[count($items) - 1]->id;

        $excludedActivities = Activity::query()
            ->select(['id', 'organizer_id', 'is_global'])
            ->whereBetween('id', [$minId, $maxId])
            ->whereNotNull('organizer_id')
            ->get();

        foreach ($excludedActivities as $obsActivity) {
            Log::warning('Excluded log row from UI presentation.', [
                'activity_id' => $obsActivity->id,
                'reason' => $obsActivity->organizer_id !== null ? 'tenant' : 'unclassified',
            ]);
        }
    }

    /**
     * @param  LengthAwarePaginator<int, Activity>  $paginator
     * @return LengthAwarePaginator<int, AuditLogEntryDto>
     */
    private function mapPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $mappedItems = collect($paginator->items())->map(
            fn (Activity $activity): AuditLogEntryDto => $this->mapActivity($activity),
        )->filter()->values();

        return new LengthAwarePaginator(
            items: $mappedItems,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: $paginator->getOptions(),
        );
    }

    private function mapActivity(Activity $activity): AuditLogEntryDto
    {
        return new AuditLogEntryDto(
            id: (int) $activity->getKey(),
            logName: $activity->log_name ?? 'default',
            event: $activity->event ?? 'unknown',
            description: $activity->description ?? '',
            actorName: $this->actorLabel($activity),
            resourceName: $this->resourceLabel($activity),
            timestamp: $activity->created_at ? $activity->created_at->toIso8601String() : '',
        );
    }

    private function actorLabel(Activity $activity): string
    {
        $causer = $activity->causer;

        if ($causer) {
            return $this->causerLabel($causer);
        }

        if ($activity->causer_type && $activity->causer_id) {
            return class_basename($activity->causer_type).' #'.$activity->causer_id;
        }

        return 'Unknown';
    }

    private function causerLabel(Model $causer): string
    {
        if (method_exists($causer, 'present')) {
            return $causer->present()->name();
        }

        return $causer->name ?? $causer->email ?? class_basename($causer).' #'.$causer->getKey();
    }

    private function resourceLabel(Activity $activity): string
    {
        if ($activity->subject) {
            return $activity->subject->name ?? $activity->subject->title ?? class_basename($activity->subject).' #'.$activity->subject->getKey();
        }

        if ($activity->subject_type && $activity->subject_id) {
            return class_basename($activity->subject_type).' #'.$activity->subject_id;
        }

        return 'Unknown';
    }
}
