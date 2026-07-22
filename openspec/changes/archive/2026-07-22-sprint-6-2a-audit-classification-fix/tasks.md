# Tasks: Sprint 6.2a Audit Classification Fix

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 3–6 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single delivery |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Enforce persisted global classification | Single delivery | `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php --filter='component excludes tenant rows, presenting only global rows'` | Existing Volt feature scenario | Revert the paired test assertion and ViewModel predicate |

## Phase 1: RED — Correct the Existing Contract

- [x] 1.1 In `tests/Feature/Admin/AuditLogTest.php`, update `component excludes tenant rows, presenting only global rows` so `Global legacy event` is asserted absent; retain explicit-global and tenant assertions and fixtures.
- [x] 1.2 Run the focused test command and record its expected RED failure: the current query still renders the unclassified row (`organizer_id` null, `is_global` false).

## Phase 2: GREEN — Constrain the Read Boundary

- [x] 2.1 In `app/ViewModels/Admin/AuditLogViewModel.php`, add `->where('is_global', true)` immediately after `->whereNull('organizer_id')` in `queryActivities()`; do not alter projection, authorization, ordering, pagination, or exclusion logging.
- [x] 2.2 Re-run the focused test command and confirm GREEN: the explicit global row renders while tenant and historical unclassified rows do not.

## Phase 3: Focused Verification

- [x] 3.1 Run `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` to retain regressions for active tenant context, exact authorization, safe projection, and pagination.
- [x] 3.2 Confirm no schema, route, policy, Volt UI, DTO, capture, backfill, or observability changes were introduced; retain the paired test-and-predicate rollback boundary.
