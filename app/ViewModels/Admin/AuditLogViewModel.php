<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AuditLogViewModel
{
    /**
     * Projects and paginates raw activities safely.
     *
     * @return LengthAwarePaginator<int, AuditLogEntryDto>
     */
    public function getLogs(int $perPage = 10): LengthAwarePaginator
    {
        // Enforce maximum bounded page size
        $boundedPerPage = min(max(1, $perPage), 50);

        try {
            $query = Activity::query()
                ->with(['causer', 'subject'])
                ->select(['id', 'log_name', 'description', 'event', 'subject_id', 'subject_type', 'causer_id', 'causer_type', 'created_at', 'is_global', 'organizer_id'])
                ->whereNull('organizer_id')->latest()
                ->orderBy('id', 'desc');

            $paginator = $query->paginate($boundedPerPage);

            $items = $paginator->items();

            // Observability: Check the exact temporal ID range for any exclusions
            if (count($items) > 0) {
                // Since it's ordered by ID desc, the first item has the max ID and the last has the min ID
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

            $mappedItems = collect($paginator->items())->map(function (Activity $activity) {

                $causerLabel = 'Unknown';

                if ($activity->causer) {
                    $causerLabel = method_exists($activity->causer, 'present')
                        ? $activity->causer->present()->name()
                        : ($activity->causer->name ?? $activity->causer->email ?? class_basename($activity->causer).' #'.$activity->causer->getKey());
                } elseif ($activity->causer_type && $activity->causer_id) {
                    $causerLabel = class_basename($activity->causer_type).' #'.$activity->causer_id;
                }

                $subjectLabel = 'Unknown';

                if ($activity->subject) {
                    $subjectLabel = $activity->subject->name ?? $activity->subject->title ?? class_basename($activity->subject).' #'.$activity->subject->getKey();
                } elseif ($activity->subject_type && $activity->subject_id) {
                    $subjectLabel = class_basename($activity->subject_type).' #'.$activity->subject_id;
                }

                return new AuditLogEntryDto(
                    id: (int) $activity->getKey(),
                    logName: $activity->log_name ?? 'default',
                    event: $activity->event ?? 'unknown',
                    description: $activity->description ?? '',
                    actorName: $causerLabel,
                    resourceName: $subjectLabel,
                    timestamp: $activity->created_at ? $activity->created_at->toIso8601String() : '',
                );
            })->filter()->values();

            return new LengthAwarePaginator(
                items: $mappedItems,
                total: $paginator->total(),
                perPage: $paginator->perPage(),
                currentPage: $paginator->currentPage(),
                options: $paginator->getOptions(),
            );

        } catch (Throwable) {
            // Redact exception detail - only report generic error without leaking context or stack traces
            Log::error('Global audit query failed with database exception.', [
                'error' => 'Database query failure',
            ]);

            throw new RuntimeException('Database query failure occurred during audit presentation.');
        }
    }
}
