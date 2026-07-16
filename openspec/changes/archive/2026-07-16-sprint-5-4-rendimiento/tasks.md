# Tasks: Sprint 5.4 — Performance & Scaling

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 240-360 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | exception-ok |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Add dependencies, MinIO config, and health wiring | PR 1 | `vendor/bin/sail composer run test -- --filter=Health` | `vendor/bin/sail artisan health:check` | composer/config/bootstrap changes only |
| 2 | Add cache, indexes, eager loading, and benchmark flow | PR 1 | `vendor/bin/sail composer run test -- --filter=EventSearchService` | `vendor/bin/sail artisan catalog:benchmark 100` | service/model/migration/command changes only |

## Phase 1: Foundation / Infrastructure

- [x] 1.1 Add `league/flysystem-aws-s3-v3` and `spatie/laravel-health` to `composer.json`; update lockfile, then verify package discovery.
- [x] 1.2 Update `.env.example`, `config/cache.php`, and `config/filesystems.php` for `CACHE_STORE=redis`, `AWS_ENDPOINT`, and `AWS_USE_PATH_STYLE_ENDPOINT` MinIO support.
- [x] 1.3 Register `Spatie\Health\HealthServiceProvider` in `bootstrap/providers.php` and wire DB/Redis/Cache/Meilisearch checks in `App\Providers\AppServiceProvider`.

## Phase 2: Core Implementation

- [x] 2.1 Create `database/migrations/*_add_catalog_indexes.php` with the 7 catalog indexes and matching `down()` drops.
- [x] 2.2 Update `app/Services/Discovery/EventSearchService.php` to cache Eloquent fallback and metadata lookups with `Cache::tags(['catalog'])`.
- [x] 2.3 Add cache flush hooks in `app/Models/Event.php`, `app/Models/Category.php`, and `app/Models/Venue.php` for `saved`/`deleted` events.
- [x] 2.4 Add eager-loading fixes in `app/Http/Controllers/Api/EventApiController.php` and `app/Http/Controllers/Public/EventWidgetController.php` to remove N+1 queries.
- [x] 2.5 Create `app/Console/Commands/CatalogBenchmarkCommand.php` and register it so `catalog:benchmark` seeds data and prints timings.

## Phase 3: Testing / Verification

- [x] 3.1 Write RED tests for `event-search` cache reuse, cache invalidation on `Event`/`Category`/`Venue`, and deterministic cache keys.
- [x] 3.2 Write RED feature tests for the health endpoint healthy/unhealthy scenarios and the S3 disk / MinIO configuration behavior.
- [x] 3.3 Write RED tests for the migration indexes, eager-loading query reduction, and `catalog:benchmark` success/invalid input behavior.

## Phase 4: Cleanup / Documentation

- [x] 4.1 Run Pint, PHPStan, and the focused Pest suite; adjust any naming or boot-order issues surfaced by the new provider and event hooks.
- [x] 4.2 Remove any temporary test scaffolding and confirm the tasks stay aligned with `openspec/changes/sprint-5-4-rendimiento/{proposal,specs,design}.md`.
