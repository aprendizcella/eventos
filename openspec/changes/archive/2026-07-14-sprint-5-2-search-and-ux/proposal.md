# Proposal: Sprint 5.2 — Search w/ Scout + Meilisearch & UX Discovery

## Intent

Add full-text search to the public catalog via Laravel Scout + Meilisearch, and extract reusable discovery components from the monolithic Volt view.

## Scope

**In**: Scout + Meilisearch SDK install; Meilisearch driver config; an explicit search service with Eloquent fallback; `Event` searchable; search bar (title/desc); existing filters retained + combined; extracted components (filter-bar, filter-chip, search-bar, result-summary, skeleton-card); public breadcrumbs; pagination; tests for indexing, search, filter combinations, fallback, and component isolation.

**Out**: SEO/sitemap/slug (5.3); widget/API (5.3); caching/CDN/performance (5.4); admin search; dynamic facet counts.

## Capabilities

- **New** `event-search`: full-text search across public events.
- **Modified** `public-catalog`: search bar, reusable UX components, active chips, loading states, breadcrumbs.

## Approach

Hybrid: Scout matches full-text → event IDs feed Eloquent query for tenant scope + structured filters (category, city, date). UX primitives extracted into `resources/views/components/catalog/`; Volt component orchestrates them.

## Affected Areas

| Area | Impact |
|------|--------|
| `composer.json` | +`laravel/scout`, `meilisearch/meilisearch-php`, `http-interop/http-factory-guzzle` |
| `config/scout.php` | New |
| `.env.example` | +`MEILISEARCH_HOST`, `MEILISEARCH_KEY` |
| `app/Models/Event.php` | +`Searchable` trait |
| `app/Services/Discovery/EventSearchService.php` | New search orchestration and Eloquent fallback |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Wire search + extracted components |
| `resources/views/components/catalog/` | New: 5 components |
| `resources/views/layouts/public.blade.php` | Breadcrumbs |
| `openspec/specs/public-catalog/spec.md` | Add search + UX delta |
| `openspec/specs/event-search/spec.md` | New |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Meilisearch unavailable in CI | Medium | `database` Scout driver fallback |
| Hybrid query breaks filters | Low | Per-combo integration tests |
| UX regression on extraction | Low | Component isolation tests |

## Rollback

Revert `composer.json`, delete `config/scout.php`, restore `Event`, revert Volt component. Keep extracted components as inert partials.

## Dependencies

`laravel/scout` + `meilisearch/meilisearch-php` (approval needed). Meilisearch container exists. Sprint 5.1 stable.

## Success Criteria

- [ ] Full-text search by title/description returns relevant events
- [ ] Search + category/city/date filters work together
- [ ] UX components render correctly (empty, loading, chips)
- [ ] Scout indexes eligible events on create/update and removes ineligible events
- [ ] CI works with `database` Scout driver
- [ ] QA pipeline passes
