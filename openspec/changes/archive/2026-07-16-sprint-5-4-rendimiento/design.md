# Design: Sprint 5.4 — Performance & Scaling

## Technical Approach

Four independent workstreams: (1) Redis tag-based caching for the Eloquent fallback in `EventSearchService`, (2) DB index migration for filtered queries, (3) eager-loading fixes in controllers, (4) `spatie/laravel-health` integration, (5) S3 adapter enablement, (6) `CatalogBenchmarkCommand`. Cache invalidation uses Eloquent `saved`/`deleted` events on `Event`, `Category`, `Venue` to flush `Cache::tags(['catalog'])`.

## Architecture Decisions

### Cache Store: Redis with Tags

| Option | Tradeoff | Decision |
|--------|----------|----------|
| File/database cache | No tag support, eviction is table-scan | Reject |
| **Redis with `Cache::tags()`** | Requires Redis, atomic flush by tag | **Accept** |
| Array cache (per-request) | No hit across requests | Reject |

Change `CACHE_STORE` from `database` to `redis`. The `redis` store already exists in `config/cache.php`. Tag-based flush means invalidation is O(1) — no key enumeration.

### Cache Invalidation: Model Events

| Option | Tradeoff | Decision |
|--------|----------|----------|
| TTL only | Stale reads until expiry | Reject |
| **Model `saved`/`deleted` observers** | Immediate flush, no stale reads | **Accept** |

Add `boot()` hooks (or dedicated observer) on `Event`, `Category`, `Venue`. On any mutation, `Cache::tags(['catalog'])->flush()`. This is safe for a single-server catalog — if multi-region caching is needed later, swap to a webhook-based approach.

### Health Package Registration (Laravel 12)

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Auto-discovery | Package may not register routes correctly | Reject |
| **Manual provider in `bootstrap/providers.php`** | Explicit, matches L12 pattern | **Accept** |

Register `Spatie\Health\HealthServiceProvider` in `bootstrap/providers.php`. Configure DB, Redis, Cache, Meilisearch checks via `Health::checks()` in `AppServiceProvider::boot()`.

### Index Migration: Single File

Single migration `add_catalog_indexes` with `algorithm=inplace` — 7 indexes across 3 tables. Each index gets a descriptive name. `down()` drops all 7.

## Data Flow

```
Request → EventSearchService
            ├─ Scout path (query present)  → Meilisearch → paginated results
            │   └─ on failure → Eloquent LIKE fallback → Cache::remember('catalog:search:...', 3600)
            └─ No-query path               → Eloquent filters → Cache::remember('catalog:list:...', 3600)

Mutation → Event/Category/Venue::saved/deleted → Cache::tags(['catalog'])->flush()
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/Discovery/EventSearchService.php` | Modify | Wrap Eloquent paginate in `Cache::tags(['catalog'])->remember()` with key based on query+filters+page |
| `app/Models/Event.php` | Modify | Add `saved`/`deleted` boot listeners to flush cache |
| `app/Models/Category.php` | Modify | Add `saved`/`deleted` boot listeners |
| `app/Models/Venue.php` | Modify | Add `saved`/`deleted` boot listeners |
| `app/Http/Controllers/Api/EventApiController.php` | Modify | Add `->with(['organizer', 'venue', 'category'])` before paginate |
| `app/Http/Controllers/Public/EventWidgetController.php` | Modify | Add `->with(['venue'])` before get() |
| `database/migrations/*_add_catalog_indexes.php` | Create | 7 indexes: event (category_id, venue_id, starts_at, ends_at), category (parent_id, slug), venue (city) |
| `app/Console/Commands/CatalogBenchmarkCommand.php` | Create | Seeds N events, runs search scenarios, prints timing |
| `app/Providers/AppServiceProvider.php` | Modify | Register health checks in `boot()` |
| `bootstrap/providers.php` | Modify | Add `HealthServiceProvider` |
| `config/cache.php` | Modify | Default store → `redis` |
| `.env.example` | Modify | Add `AWS_ENDPOINT`, change `CACHE_STORE=redis` |
| `composer.json` | Modify | Add `league/flysystem-aws-s3-v3`, `spatie/laravel-health` |
| `config/filesystems.php` | No change | S3 disk already configured with `endpoint` + `use_path_style_endpoint` |

## Interfaces / Contracts

**Cache key scheme** (inside `EventSearchService`):

```php
// Eloquent fallback (no query)
$key = 'catalog:list:' . md5(serialize($filters) . "|page={$page}|perPage={$perPage}");

// Eloquent LIKE fallback (Scout unavailable)
$key = 'catalog:search:' . md5($query . serialize($filters) . "|page={$page}|perPage={$perPage}");

Cache::tags(['catalog'])->remember($key, 3600, fn () => $query->paginate($perPage));
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Cache keys are deterministic for same input | `EventSearchServiceTest` with `Cache::shouldReceive` |
| Unit | Model boot flushes cache on save/delete | Mock `Cache::tags()->flush()` on Event/Category/Venue |
| Feature | Index migration applies and rolls back | Run migration, check `EXPLAIN`, run `down`, confirm clean |
| Feature | Widget + API controllers eager-load relations | Assert `withCount` or query log |
| Feature | Health endpoint responds | `get('/health')` and assert JSON structure |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

**Cache store switch**: Change `CACHE_STORE` to `redis` in `.env`. Stale `database` cache entries are harmless (TTL will expire them). Redis must be available before deploy.

**Index migration**: Use `algorithm=inplace` (MySQL/Percona) to avoid table locks. Run during low-traffic window. Rollback via `php artisan migrate:rollback`.

**New packages**: Run `composer install` after deploy. S3 adapter is optional — `local` disk remains default.

## Open Questions

- [ ] Should the `widget` controller also cache its response, or is the N+1 fix sufficient? (Deferred — widget is low-traffic embed)
- [ ] Confirm Meilisearch health check URI: `MeilisearchCheck` or custom HTTP check?
