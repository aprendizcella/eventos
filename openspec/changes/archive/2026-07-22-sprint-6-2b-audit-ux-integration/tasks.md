# Tasks: Sprint 6.2b Audit UX Integration

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 520–720 |
| 400-line budget risk | High |
| Chained PRs recommended | No — maintainer-approved single delivery |
| Suggested split | One bounded delivery |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: High

The standard 400-line review budget is exceeded; the approved 800-line single-delivery exception applies. No PR workflow is planned.

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Filtered, safe responsive audit surface | Single delivery | `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` | `Volt::test('admin.audit-log')` feature scenarios | Revert filter DTO, ViewModel, Volt page, and paired tests together |

## Phase 1: RED — Filter and Safety Contracts

- [x] 1.1 In `tests/Feature/Admin/AuditLogTest.php`, add failing fixtures/tests proving only explicit-global rows participate in allowed `log_name`, `event`, and inclusive paired-date filters; tenant and unclassified rows stay absent and uncounted.
- [x] 1.2 Add RED datasets for unknown/injected allowlist values and partial, reversed, non-ISO, and over-90-day dates; assert validation/safe normalization preserves the prior safe result rather than broadening it.
- [x] 1.3 Add RED `Volt::test('admin.audit-log')` coverage from page two: apply/reset filters, page one, chips, filtered total, stable equal-timestamp navigation, header/read-only cue, desktop/mobile containers, and loading/empty/generic-error states.
- [x] 1.4 Add RED safety regression coverage for exact `super_admin` route/component-update denial plus absent secrets, raw models, payload search, exports, charts, write controls, and generic-table features.
- [x] 1.5 Run `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` and record the expected RED failures before production edits.

### Strict-TDD Evidence Exception

**Authorization**: User-approved on 2026-07-22: `Autorizas registrar la excepción de evidencia strict-TDD para 6.2b y continuar con la verificación final?` — `si`.

This exception applies **only** to the missing historical individual RED traces for tasks 1.1–1.5. It does not recreate or claim to recreate those original RED traces. All corrective RED/GREEN evidence remains required and retained, together with passing runtime tests, static analysis, and the established security boundaries. Final verification MUST report this exception transparently.

## Phase 2: GREEN — Bounded Server Query

- [x] 2.1 Create `app/DataTransferObjects/Admin/AuditLogFilterDto.php` as a final readonly transport for validated nullable allowlist values and inclusive date bounds.
- [x] 2.2 Update `app/ViewModels/Admin/AuditLogViewModel.php` so `getLogs(AuditLogFilterDto $filter, int $perPage = 10)` retains the verified `organizer_id IS NULL AND is_global = true` predicate first, applies only closed allowlists/date bounds, stable ordering, and returns the filtered paginator total.
- [x] 2.3 Re-run the focused suite until GREEN; retain the existing selected scalar fields, relations, DTO mapping, exception redaction, and bounded page size without selecting/searching payload JSON.

## Phase 3: GREEN — Volt Controls and Responsive Records

- [x] 3.1 Update `resources/views/livewire/admin/audit-log.blade.php` with deferred draft controls; make `applyFilters()` atomically validate/promote scalar state and `resetFilters()` clear it, with both calling `resetPage()`.
- [x] 3.2 Render the existing platform-report header/card and dark-mode conventions: read-only cue, filtered count from `total()`, active chips, reset control, loading, empty, and generic unavailable states.
- [x] 3.3 Render the same safe DTO fields as `md` desktop table rows and below-`md` stacked activity cards with `wire:key`; satisfy the prepared markup assertions without partial/error-detail rows.

## Phase 4: Regression and Scope Verification

- [x] 4.1 Run `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php`; confirm all prepared contracts are GREEN.
- [x] 4.2 Verify no route, policy, schema, capture, backfill, navigation, or Sprint 6.2a artifact changes; retain the single rollback boundary.
