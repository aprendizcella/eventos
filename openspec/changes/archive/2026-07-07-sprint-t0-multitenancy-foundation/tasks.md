# Tasks: Sprint T0 — Multitenancy Foundation

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 350-600 |
| 400-line budget risk | High |
| Chained PRs recommended | No |
| Suggested split | Sprint T0a → Sprint T0b → Sprint T0c |
| Delivery strategy | sequential-main-commits |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Slice | Notes |
|------|------|-------|-------|
| 1 | Package + config baseline | Sprint T0a | Install package, publish config, wire tenant model. |
| 2 | Tenant resolution + runtime | Sprint T0b | Custom finder, middleware, route/session fallback. |
| 3 | Async + verification | Sprint T0c | Queue context, listeners, tenant isolation tests. |

## Sprint T0a: Package / Config

- [x] 1.1 Add `spatie/laravel-multitenancy` to `composer.json` and publish `config/multitenancy.php`.
- [x] 1.2 Make `Organizer` satisfy the package tenant contract/trait without changing the single-DB model.
- [x] 1.3 Add any required config/database bootstrap for tenant-aware runtime in a single database.

## Sprint T0b: Tenant Resolution / Runtime

- [x] 2.1 Create `app/Support/Multitenancy/OrganizerTenantFinder.php` with host-first resolution and route fallback only for internal organizer URLs.
- [x] 2.2 Register the finder/middleware in `bootstrap/app.php` and keep `organizer.detect` working during transition without overriding a resolved host tenant.
- [x] 2.3 Confirm public tenant-aware requests resolve by the root-domain host from `APP_URL`, internal organizer URLs resolve by route when no host match exists, and the root domain stays tenant-less for superadmin.

## Sprint T0c: Async / Verification

- [x] 3.1 Make queued jobs/listeners tenant-aware where organizer context matters.
- [x] 3.2 Add feature tests for host resolution, route fallback, and cross-organizer denial.
- [x] 3.3 Add queue/context tests proving tenant restoration on async execution and run focused QA.
