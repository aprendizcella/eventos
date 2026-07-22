# Design: Sprint 6.2a Audit Classification Fix

## Technical Approach

Use strict RED-GREEN TDD to align the existing global-audit read model with the delta specification. First amend the mixed-classification Volt feature contract. It must prove that only an explicit global row (`organizer_id IS NULL`, `is_global = true`) renders, while tenant and historical unclassified rows do not. Confirm this test is RED against the current query. Then add the canonical `is_global = true` predicate to the existing query immediately after `whereNull('organizer_id')`.

No new class, route, policy, schema, migration, capture logic, DTO field, pagination behavior, or observability behavior is introduced.

## Architecture Decisions

| Decision | Alternatives considered | Rationale |
|---|---|---|
| Constrain the existing ViewModel query | New model scope; post-query filtering; infer from tenant/payload | `AuditLogViewModel::queryActivities()` is the sole read boundary. A database predicate is fail-closed, minimal, and preserves existing ordering, eager loading, and pagination. |
| Amend the existing feature contract before production code | Add a separate unit test; change query first | The current rendered-output test explicitly accepts the defect. Reversing its legacy assertion creates a focused behavioral RED test at the Volt boundary. |
| Leave excluded-row warning behavior unchanged | Log unclassified exclusions too | `logExcludedActivities()` is explicitly out of scope; changing it would broaden the fix without a requirement. |

## Data Flow

```text
super_admin request -> admin.audit-log Volt component
  -> AuditLogViewModel::getLogs()
  -> queryActivities(): organizer_id IS NULL AND is_global = true
  -> safe AuditLogEntryDto projection -> rendered table
```

The persisted fields alone classify visibility. An active tenant context, descriptions, payloads, and labels cannot make a row global.

## File Changes

| File | Action | Description |
|---|---|---|
| `tests/Feature/Admin/AuditLogTest.php` | Modify | Amend `component excludes tenant rows, presenting only global rows`: retain the explicit-global and tenant fixtures; retain/create the unclassified fixture; change the final legacy visibility assertion to `assertDontSee`. Run it RED before application code. |
| `app/ViewModels/Admin/AuditLogViewModel.php` | Modify | In `queryActivities()`, add `->where('is_global', true)` directly after `->whereNull('organizer_id')`. |

## Interfaces / Contracts

The existing read contract becomes exact:

```php
Activity::query()
    ->whereNull('organizer_id')
    ->where('is_global', true);
```

No HTTP, DTO, route, policy, database, or public API interface changes.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Feature (RED/GREEN) | Mixed global, tenant, and unclassified rows | Update the named Volt test in `tests/Feature/Admin/AuditLogTest.php`; assert only the explicit global description renders. Confirm failure before the query edit, then success after it. |
| Feature regression | Tenant-context isolation, exact authorization, safe projection, pagination | Retain and run the complete `AuditLogTest.php` suite. No new browser or unit test is needed. |

Focused RED/GREEN command:

```bash
vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php --filter='component excludes tenant rows, presenting only global rows'
```

Regression command:

```bash
vendor/bin/sail artisan test --compact tests/Feature/Admin/AuditLogTest.php
```

Before acceptance, run `vendor/bin/sail composer run pint -- --test` and `vendor/bin/sail composer run phpstan`.

## Safe Data and Authorization Invariants

- Only `organizer_id IS NULL AND is_global = true` is rendered; tenant and unclassified records remain persisted and unchanged.
- The route middleware remains `role:super_admin`; the Volt component continues `ActivityPolicy::viewAny()` reauthorization via `hasGlobalRole('super_admin')`, including active tenant context.
- The existing selected scalar columns and `AuditLogEntryDto` projection remain unchanged; payloads, `properties`, `attribute_changes`, and secrets remain absent.

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary changes.

## Migration / Rollout

No migration required. Existing ownership columns and their database invariant remain unchanged. Deploy as a normal small application change; historical unclassified rows intentionally disappear from this UI. Roll back by reverting the predicate and the paired contract assertion together; no data rollback is needed.

## Open Questions

None.

## Next SDD Route

After this design, the next SDD route is `sdd-tasks`.
