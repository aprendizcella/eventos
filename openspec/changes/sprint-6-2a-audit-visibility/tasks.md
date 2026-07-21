# Tasks: Sprint 6.2a Audit Visibility

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 600–760 authored lines (tests included) |
| 800-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR, behavior-oriented commits |
| Delivery strategy | single-pr-default |
| Chain strategy | not applicable |

Below the 800-line review budget; the policy conflict is resolved by excluding unclassifiable rows from every query result and UI, including for `super_admin`.

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Classification contract, safe projection, and future `organizer_id` metadata | PR 1 | `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php --filter=classification` | N/A: no runtime harness before the page exists | Revert ViewModel/DTO/query and activity metadata changes |
| 2 | Authorized Volt page, route, states, and regression coverage | PR 1 | `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` | Visit `/admin/audit-logs` as `super_admin` and denied users; assert no JS errors | Revert route, Volt view, policy, and audit tests |

## Phase 1: Security and data contract (RED → GREEN)

- [x] 1.1 RED: Add Pest cases for verified `super_admin` success, guest authentication, non-admin 403, denied Livewire request, and no query on denial (path assumption: new `tests/Feature/Admin/AuditLogTest.php`).
- [x] 1.2 GREEN: Add `app/Policies/ActivityPolicy.php` and wire the route’s `auth`, `verified`, `global.admin`, and exact `super_admin` boundary without weakening existing admin routes.
- [x] 1.3 RED: Test authoritative `organizer_id` / `is_global` classification, and assert unclassifiable rows are absent for `super_admin`, with a structured warning that excludes payload data and preserves Team 0.
- [x] 1.4 GREEN: Implement `app/ViewModels/Admin/AuditLogViewModel.php` and its DTO/query seam; filter unclassifiable rows before projection, never infer from request tenant, and select only required columns.
- [x] 1.5 RED/GREEN: Test and implement future-row metadata at the activity seam, preserving secret allowlisting.

## Phase 2: Safe presentation and navigation (RED → GREEN)

- [x] 2.1 RED: Test scalar projection, escaped labels, omission of payload/secrets, deterministic `created_at,id` ordering, bounded pagination, empty state, safe query errors, and no “Unclassified” HTML.
- [x] 2.2 GREEN: Implement the readonly DTO/ViewModel projection and pagination; reject or omit filter/sort inputs because they are out of scope, preserving an allowlist boundary for future additions.
- [x] 2.3 RED → GREEN: Create `resources/views/livewire/admin/audit-log.blade.php` using the inspected Volt SFC convention; add loading, empty, error, escaped table, `wire:key`, and pagination states with no raw model serialization.

## Phase 3: Integration and regression

- [x] 3.1 RED → GREEN: Modify `routes/web.php` with the named `/admin/audit-logs` Volt route and exact middleware/policy enforcement; test route rendering and Livewire authorization.
- [x] 3.2 Run focused Pest tests, then `vendor/bin/sail composer run test`, Pint, and PHPStan; verify no payload, cross-tenant row, or unclassifiable row appears.
- [x] 3.3 Keep all artifacts aligned on the fail-closed policy; rollback is code-only by reverting route, policy, ViewModel/DTO, Volt view, metadata seam, and tests.
