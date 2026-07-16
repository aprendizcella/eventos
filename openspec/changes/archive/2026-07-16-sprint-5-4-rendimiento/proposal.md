# Proposal: Sprint 5.4 — Performance & Scaling

## Intent

Reduce DB load on public catalog reads, fix missing indexes slowing filtered queries, add production health observability, and provide benchmarking tooling. Current catalog hits the DB uncached on every request, key columns lack indexes, and the S3 adapter is missing despite a configured disk.

## Scope

### In Scope
- Redis cache layer for EventSearchService Eloquent fallback and metadata queries (categories, cities)
- DB indexes on `event` (category_id, venue_id, starts_at, ends_at), `category` (parent_id, slug), `venue` (city) + N+1 eager-loading fixes in controllers
- Install `league/flysystem-aws-s3-v3` with MinIO env configuration
- Install `spatie/laravel-health` for DB, Redis, Cache, Meilisearch checks
- Catalog benchmark Artisan command (`php artisan catalog:benchmark`)

### Out of Scope
- Cursor pagination (deferred to later sprint)
- Scout/Meilisearch query caching (Scout manages its own cache internally)
- Redis adoption beyond cache store (queues remain separate)

## Capabilities

### New Capabilities
- `health-monitoring`: Deep health endpoint covering DB, Redis, Cache, Meilisearch connectivity
- `load-testing`: Artisan command seeding N events and measuring EventSearchService throughput
- `asset-storage`: S3/MinIO filesystem adapter for scalable file uploads

### Modified Capabilities
- `event-search`: Add Redis tag-based caching for the Eloquent fallback query path and metadata lookups (Search Fallback, Searchable Filter Attributes). Cache busts on event/category/venue mutations.

## Approach

Switch `CACHE_STORE` to `redis` and wrap catalog queries in `Cache::remember()` with cache tags for invalidation. Single migration for 7 missing indexes. Audit controllers for `->with()` eager loading. Install `league/flysystem-aws-s3-v3`, add `AWS_ENDPOINT` / `AWS_USE_PATH_STYLE_ENDPOINT` to `.env.example`. Install `spatie/laravel-health` and register checks in `bootstrap/app.php`. Create `App\Console\Commands\CatalogBenchmarkCommand` that seeds events and measures execution time.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/Discovery/EventSearchService.php` | Modified | Cache Eloquent fallback + metadata |
| `app/Http/Controllers/Public/EventWidgetController.php` | Modified | Add eager loading, fix N+1 |
| `app/Http/Controllers/Api/EventApiController.php` | Modified | Add eager loading to paginated queries |
| `database/migrations/` | Modified | Add indexes on event, category, venue |
| `config/cache.php` | Modified | Default to `redis` store |
| `.env.example` | Modified | MinIO vars (`AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`) |
| `composer.json` | Modified | Add `flysystem-aws-s3-v3`, `spatie/laravel-health` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Stale cache serves outdated catalog | Medium | Tag-based invalidation on event/category/venue save events |
| Index migration locks large `event` table | Low | Use `algorithm=inplace`, run during low traffic |
| S3 disk breaks existing upload workflows | Low | Keep `local` disk default; scope S3 to new features only |

## Rollback Plan

Revert `CACHE_STORE` to `database`, drop added indexes via `down()`, remove composer packages, restore `.env.example`.

## Dependencies

- Redis server in all environments
- MinIO or S3-compatible service for asset storage (optional for local dev)

## Success Criteria

- [ ] Eloquent fallback queries served from Redis cache (measured via benchmark command)
- [ ] All 7 indexes created; `EXPLAIN` shows index usage on filtered queries
- [ ] Health endpoint reports DB, Redis, Cache, Meilisearch status correctly
- [ ] `php artisan catalog:benchmark` outputs timing results without error
- [ ] `composer qa` passes (pint, phpstan, tests)
