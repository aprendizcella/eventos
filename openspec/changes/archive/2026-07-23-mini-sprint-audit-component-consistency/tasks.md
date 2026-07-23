# Tasks: Audit Component Consistency

Presentation-only refactor of `admin.audit-log`; queries, DTOs, policies, routes, and persisted data remain unchanged.

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 120–220 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | One presentation-only delivery |
| Delivery strategy | auto-chain (single delivery) |
| Chain strategy | completed |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: completed
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Reuse controls and align audit hierarchy | Single delivery | `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` | N/A — Volt Feature tests render and invoke the Livewire lifecycle; browser coverage only if the Alpine picker fails | Revert audit view and paired assertions |

## Phase 1: RED — Presentation Contracts

- [x] 1.1 In `tests/Feature/Admin/AuditLogTest.php`, add failing rendered-output assertions for shared select/date IDs, labels, placeholders, allowlisted values, `wire:model` bindings, and compact Apply/conditional Reset actions.
- [x] 1.2 Add failing seeded-row assertions for `Global Audit Logs` → `Read-only audit trail` → `Immutable records`, responsive-header/filter-card/result-card classes, and preserved desktop/mobile record hooks.
- [x] 1.3 Add failing apply/reset assertions proving active `Log: auth` and `Event: login` chips, visible count/row, reset removal, and page-one pagination; record each focused Sail failure before production edits.

## Phase 2: GREEN — Bounded Volt Refactor

- [x] 2.1 In `resources/views/livewire/admin/audit-log.blade.php`, add `declare(strict_types=1);` and replace raw selects with labeled `x-form.select` value-to-label maps while retaining IDs, placeholders, and draft bindings.
- [x] 2.2 Replace raw dates with `x-form.date` using each draft property for `:value` and `wire:model`; retain `wire:submit`, existing error keys, and all apply/reset semantics.
- [x] 2.3 Replace Apply and Reset with `x-ui.button class="!w-auto"`; preserve Reset `type="button"` and `wire:click="resetFilters"`.
- [x] 2.4 Make only the required header, filter-card, and result-card hierarchy/utility changes; retain copy, loading/error/empty branches, `md` containers, safe fields, and every `wire:key`.
- [x] 2.5 Run the focused Sail suite after each GREEN increment until every RED contract passes; do not modify query, DTO, ViewModel, route, policy, navigation, or shared components.

## Phase 3: REFACTOR — Focused Verification

- [x] 3.1 Simplify only duplicated test setup/assertions without weakening rendered, state, strict-types, safe-state, or authorization coverage; rerun the focused Sail suite.
- [x] 3.2 Run `vendor/bin/sail bin pint --dirty --format agent`, then `vendor/bin/sail composer run phpstan`; rerun `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` and retain RED→GREEN command evidence.

## Corrective Rerun 2/2 Evidence

- [x] Validator remediation: the seeded rendered-markup contract now verifies that `audit-log-desktop-records` retains `rounded-xl border border-gray-200 bg-white shadow-sm` on the same desktop record container.
- Focused assertion: `vendor/bin/sail artisan test --compact --filter='audit presentation retains the report-aligned hierarchy and responsive records'` → `1 passed (7 assertions)`.
- Focused suite: `vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php` → `33 passed (157 assertions)`.
- Formatting: `vendor/bin/sail bin pint --dirty --format agent` → passed.
