# Apply Progress: Sprint 5.1 — Public Catalog

**Mode**: Strict TDD
**Delivery**: exception-ok (single commit, no PR)
**Date**: 2026-07-12

## Completed Tasks

| # | Task | Status |
|---|------|--------|
| 2.1 | Replace root welcome page with public catalog route | ✅ |
| 2.2 | Create public catalog component with root-domain and tenant-domain scoping | ✅ |
| 2.3 | Implement filters for category, city, and date | ✅ |
| 2.4 | Create reusable public event card component | ✅ |
| 3.1 | Create public event detail route and component | ✅ |
| 3.2 | Render event metadata, organizer context, and calendar actions | ✅ |
| 3.3 | Add clear CTA to the existing checkout flow | ✅ |
| 4.1 | Feature tests for root-domain catalog scope | ✅ |
| 4.2 | Feature tests for tenant-domain catalog scope | ✅ |
| 4.3 | Feature tests for public detail and checkout entry | ✅ |
| 4.4 | Run Pint, PHPStan, and Pest | ✅ |

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 2.1–2.2 | `tests/Feature/Catalog/PublicCatalogTest.php` | Feature | ✅ 761/761 | ✅ Written | ✅ Passed | ✅ 4 cases | ✅ Clean |
| 2.3 | `tests/Feature/Catalog/PublicCatalogFilterTest.php` | Feature | ✅ 761/761 | ✅ Written | ✅ Passed | ✅ 4 cases | ✅ Clean |
| 2.4 | `tests/Feature/Catalog/PublicCatalogTest.php` | Feature | ✅ 761/761 | ✅ Written | ✅ Passed | ✅ 4 cases | ✅ Clean |
| 3.1–3.3 | `tests/Feature/Catalog/PublicEventDetailTest.php` | Feature | ✅ 761/761 | ✅ Written | ✅ Passed | ✅ 5 cases | ✅ Clean |
| 4.1–4.2 | `tests/Feature/Events/EventScopeTest.php` | Feature | ✅ 761/761 | ✅ Written | ✅ Passed | ✅ 3 cases | ✅ Clean |

## Test Summary

- **Total tests written (new)**: 16
- **Total tests passing**: 777 (761 baseline + 16 new)
- **Layers used**: Feature (16)
- **Approval tests**: None — no refactoring tasks
- **Pure functions created**: 0 — Volt components with database queries

## Files Changed

| File | Action | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | Replaced welcome root with catalog Volt route; added event detail route |
| `app/Models/Event.php` | Modified | Added `scopePublic()` for visibility filtering |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Created | Public catalog Volt component with filters and tenant scoping |
| `resources/views/livewire/public/events/event-detail-public.blade.php` | Created | Public event detail Volt component with metadata, calendar, checkout CTA |
| `resources/views/components/catalog/event-card.blade.php` | Created | Reusable event card component for catalog grid |
| `tests/Feature/Catalog/PublicCatalogTest.php` | Created | Root domain, tenant domain, visibility, and unpublished event tests |
| `tests/Feature/Catalog/PublicCatalogFilterTest.php` | Created | Category, city, date filter, and empty state tests |
| `tests/Feature/Catalog/PublicEventDetailTest.php` | Created | Detail page, private event 404, checkout link, organizer name, calendar actions |
| `tests/Feature/Events/EventScopeTest.php` | Created | Event scopePublic unit tests (public, password, chained with published) |
| `tests/Feature/ExampleTest.php` | Modified | Added `LazilyRefreshDatabase` for catalog route compatibility |

## Deviations from Design

None — implementation matches design exactly.

## Issues Found

- **ExampleTest regression**: The pre-existing `ExampleTest` did not use `LazilyRefreshDatabase` and failed when the root route started querying the database. Fixed by adding the trait.

## Remaining Tasks

- [x] Phase 5: Archive — to be done after verification passes.

## Status

11/11 tasks complete. Ready for verify.
