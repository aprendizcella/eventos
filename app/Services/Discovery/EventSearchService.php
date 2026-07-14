<?php

declare(strict_types=1);

namespace App\Services\Discovery;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Builder as ScoutBuilder;
use Throwable;

/**
 * Orchestrates search across Scout/Meilisearch with Eloquent LIKE fallback.
 *
 * When Scout is available and a query is provided, the service delegates
 * to Scout for full-text search and applies structured filters as Scout
 * filter expressions. When Scout is unavailable or no query is given,
 * it falls back to Eloquent WHERE LIKE on title and description.
 *
 * The service preserves Meilisearch relevance order for text queries
 * and uses start-date ascending when no text query is present.
 */
final class EventSearchService
{
    private const PER_PAGE = 12;

    /**
     * Search events by text query and/or structured filters.
     *
     * The date filter acts as an inclusive lower bound (from-date):
     * events on or after the given date are included. Date ranges
     * remain deferred per the product roadmap.
     *
     * @param  array<string, mixed>  $filters  Structured filters: organizer_id, category_id, city, from_date.
     * @return LengthAwarePaginator<int, Event>
     */
    public function search(
        string $query = '',
        array $filters = [],
        int $perPage = self::PER_PAGE,
    ): LengthAwarePaginator {
        $baseQuery = $this->buildBaseQuery();

        $query = trim($query);

        if ($query !== '') {
            return $this->searchWithScout($baseQuery, $query, $filters, $perPage);
        }

        $this->applyEloquentFilters($baseQuery, $filters);
        $baseQuery->orderBy('starts_at');

        /** @var LengthAwarePaginator<int, Event> */
        return $baseQuery->paginate($perPage);
    }

    /**
     * Build the base query with eligibility and tenant scoping.
     *
     * @return EloquentBuilder<Event>
     */
    private function buildBaseQuery(): EloquentBuilder
    {
        /** @var EloquentBuilder<Event> $query */
        $query = Event::query()
            ->published()
            ->public()
            ->with(['organizer', 'venue', 'category']);

        $tenant = Organizer::current();

        if ($tenant !== null) {
            $query->where('organizer_id', $tenant->id);
        }

        return $query;
    }

    /**
     * Attempt Scout search; fallback to Eloquent LIKE on failure.
     *
     * When Scout succeeds, the results preserve the relevance order
     * returned by the search engine (Meilisearch). In the fallback
     * path, results are ordered by start date ascending.
     *
     * ── Hybrid Strategy for from-date filter ──
     *
     * Scout v10's Builder::where() only supports exact match ($field, $value).
     * The >= operator required for inclusive from-date is not available.
     * Instead of faking an unsupported operator:
     *
     *  1. Exact-match filters (category, city, organizer)  → Scout where()
     *  2. From-date filter (>= lower bound)                 → Eloquent hydration callback
     *
     * This means Scout retrieves candidate IDs from Meilisearch using text
     * relevance + exact filters, then Eloquent applies the date range during
     * hydration. Pagination totals may slightly overcount (Scout counts hits
     * in Meilisearch before the date filter), but per-page items are correct.
     *
     * @param  EloquentBuilder<Event>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Event>
     */
    private function searchWithScout(
        EloquentBuilder $baseQuery,
        string $query,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        try {
            /** @var ScoutBuilder<Event> $scoutBuilder */
            $scoutBuilder = Event::search($query);

            // Step 1: apply exact-match filters (category, city, organizer) via Scout's native where()
            $this->applyScoutFiltersWithoutDate($scoutBuilder, $filters);

            // Step 2: apply from-date filter via Eloquent hydration callback
            // (Scout v10 where() does not support >=, see hybrid strategy doc above)
            if (!empty($filters['date'])) {
                $date = (string) $filters['date'];
                $scoutBuilder->query(fn (EloquentBuilder $q) => $q->where('starts_at', '>=', $date));
            }

            /** @var LengthAwarePaginator<int, Event> */
            return $scoutBuilder->paginate($perPage);
        } catch (Throwable $e) {
            Log::warning('Scout search unavailable, falling back to Eloquent LIKE', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            $this->applyEloquentFilters($baseQuery, $filters);
            $baseQuery->where(function (EloquentBuilder $q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
            $baseQuery->orderBy('starts_at');

            /** @var LengthAwarePaginator<int, Event> */
            return $baseQuery->paginate($perPage);
        }
    }

    /**
     * Apply exact-match structured filters to a Scout builder.
     *
     * The date filter is intentionally excluded: Scout v10's Builder::where()
     * only supports exact match, so the from-date (>=) filter is handled
     * separately via an Eloquent hydration callback in searchWithScout().
     *
     * @param  ScoutBuilder<Event>  $builder
     * @param  array<string, mixed>  $filters
     * @return ScoutBuilder<Event>
     */
    private function applyScoutFiltersWithoutDate(ScoutBuilder $builder, array $filters): ScoutBuilder
    {
        if (isset($filters['organizer_id'])) {
            $builder->where('organizer_id', (int) $filters['organizer_id']);
        }

        if (isset($filters['category_id'])) {
            $builder->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['city'])) {
            $builder->where('venue_city', (string) $filters['city']);
        }

        return $builder;
    }

    /**
     * Apply structured filters to an Eloquent query builder.
     *
     * @param  EloquentBuilder<Event>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyEloquentFilters(EloquentBuilder $query, array $filters): void
    {
        if (isset($filters['organizer_id'])) {
            $query->where('organizer_id', (int) $filters['organizer_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['city'])) {
            $query->whereHas('venue', function (EloquentBuilder $q) use ($filters): void {
                $q->where('city', (string) $filters['city']);
            });
        }

        if (!empty($filters['date'])) {
            // Inclusive lower-bound: events on or after the selected date.
            // Uses WHERE starts_at >= 'YYYY-MM-DD 00:00:00' so that an event
            // on the same date (e.g. 2026-08-15 at 20:00) is included.
            $query->where('starts_at', '>=', (string) $filters['date']);
        }
    }
}
