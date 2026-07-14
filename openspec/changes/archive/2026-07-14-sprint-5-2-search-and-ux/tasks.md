# Tasks: Sprint 5.2 — Search w/ Scout + Meilisearch & UX Discovery

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 450-700 |
| 400-line budget risk | High |
| Chained PRs recommended | No |
| Suggested split | Work unit 1 foundation → work unit 2 UX/wiring → work unit 3 tests/docs, committed directly to main |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Delivery unit | Notes |
|------|------|-----------|-------|
| 1 | Scout + Meilisearch foundation | Work unit 1 | `composer.json`, `config/scout.php`, `.env.example`, `app/Models/Event.php`, search service |
| 2 | Catalog search UX + URL state | Work unit 2 | `event-list-public`, `components/catalog/*`, `layouts/public.blade.php` |
| 3 | Verification + docs | Work unit 3 | `tests/Feature/**`, `tests/Unit/**`, `docs/**`, OpenSpec notes |

## Phase 1: Foundation / Infrastructure

- [x] 1.1 Add `laravel/scout`, `meilisearch/meilisearch-php`, and `http-interop/http-factory-guzzle` to `composer.json` and lockfile; keep install path ready for Sail.
- [x] 1.2 Create `config/scout.php` with Meilisearch driver and queue-friendly defaults; keep fallback in the search service, not as a fake Scout runtime driver.
- [x] 1.3 Update `.env.example` with `SCOUT_DRIVER`, `MEILISEARCH_HOST`, and `MEILISEARCH_KEY` placeholders.
- [x] 1.4 Update `app/Models/Event.php` with `Searchable`, searchable text fields, filterable attributes, `shouldBeSearchable()`, and explicit removal for published/public eligibility.
- [x] 1.5 Configure Meilisearch searchable attributes (`title`, `description`) and filterable attributes (`organizer_id`, `category_id`, `venue_city`, `starts_at`).
- [x] 1.6 Create `app/Services/Discovery/EventSearchService.php` for Scout orchestration, structured filters, relevance preservation, and Eloquent/LIKE fallback.

## Phase 2: Core Implementation

- [x] 2.1 Update `resources/views/livewire/public/events/event-list-public.blade.php` to add `#[Url]` search/filter state, debounced search, pagination, and search + filter composition.
- [x] 2.2 Extract `resources/views/components/catalog/search-bar.blade.php`, `filter-bar.blade.php`, `filter-chip.blade.php`, `result-summary.blade.php`, and `skeleton-card.blade.php` from the monolith.
- [x] 2.3 Refactor `resources/views/components/catalog/event-card.blade.php` to show minimum available price, sold-out state, and future image slot extension point.
- [x] 2.4 Update `resources/views/layouts/public.blade.php` to expose an optional public breadcrumb slot used by the event detail.
- [x] 2.5 Wire the search service into the catalog, including Eloquent/LIKE fallback and failure logging without breaking category/city/date filters.

## Phase 3: Testing / Verification

- [x] 3.1 RED: Add `tests/Unit/Models/EventSearchTest.php` for searchable payload and eligible-ineligible indexing rules.
- [x] 3.2 RED: Add `tests/Feature/PublicCatalogSearchTest.php` covering search + filter combination, URL state restoration, empty state actions, and pagination at 12 per page.
- [x] 3.3 RED: Add `tests/Feature/EventIndexSyncTest.php` asserting async index queueing after commit and removal when an event leaves indexed states.
- [x] 3.4 RED: Add `tests/Feature/MeilisearchFallbackTest.php` using the `database` Scout driver and a real Meilisearch integration test that skips unless `MEILISEARCH_HOST` is set.
- [x] 3.5 GREEN: Make the backend and UI changes pass with the smallest code needed; verify all affected tests under `tests/Unit` and `tests/Feature`.

## Phase 4: Cleanup / Documentation

- [x] 4.1 Update the relevant OpenSpec notes under `openspec/changes/sprint-5-2-search-and-ux/specs/**` if implementation details require clarification.
- [x] 4.2 Add deploy/run notes for `vendor/bin/sail artisan scout:sync-index-settings` and initial import in project docs.
- [x] 4.3 Run QA: `vendor/bin/sail bin pint --dirty --format agent` ✅, `vendor/bin/sail composer run phpstan` ✅, `vendor/bin/sail composer run test` ✅.
