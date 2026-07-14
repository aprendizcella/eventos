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
     * @param  array<string, mixed>  $filters  Structured filters: organizer_id, category_id, city, date.
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

            $scoutBuilder = $this->applyScoutFilters($scoutBuilder, $filters);

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
     * Apply structured filters to a Scout builder.
     *
     * @param  ScoutBuilder<Event>  $builder
     * @param  array<string, mixed>  $filters
     * @return ScoutBuilder<Event>
     */
    private function applyScoutFilters(ScoutBuilder $builder, array $filters): ScoutBuilder
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

        // Date range filtering is handled in the Eloquent fallback path.

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
            $query->whereDate('starts_at', (string) $filters['date']);
        }
    }
}
