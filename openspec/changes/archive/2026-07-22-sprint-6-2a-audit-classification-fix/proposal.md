# Proposal: Sprint 6.2a Audit Classification Fix

## Intent

Correct the global audit read boundary so historical unclassified rows cannot be displayed as global events. The implementation, test, and existing Sprint 6.2a visibility contract must agree on the persisted predicate: `organizer_id IS NULL AND is_global = true`.

## Scope

### In Scope
- Use strict TDD: first make the mixed-classification feature case fail by requiring an explicit global row to render while tenant and unclassified rows do not.
- Add `is_global = true` to the existing `AuditLogViewModel::queryActivities()` global query, immediately after `whereNull('organizer_id')`.
- Retain regression coverage for an active tenant context and run the focused case plus `tests/Feature/Admin/AuditLogTest.php`.
- Preserve the exact `super_admin` authorization boundary and the existing safe DTO scalar projection.

### Out of Scope
- UX redesign, filters, counts, pagination changes, or exclusion-observability expansion.
- Schema, capture seam, historical-data backfill, or logging redesign.
- Route, policy, Volt component, DTO contract, or authorization changes.

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- `global-audit-visibility`: enforce its persisted global classification boundary consistently in the existing read model and feature coverage.

## Approach

Follow RED-GREEN. Update the existing rendered-output classification test to expose the defect: global is visible; tenant and unclassified rows are absent. Confirm the focused test fails, then constrain the sole query with `whereNull('organizer_id')->where('is_global', true)`. Do not infer classification from tenant context or JSON payloads. Run the focused and full audit feature suites, Pint, and PHPStan.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `app/ViewModels/Admin/AuditLogViewModel.php` | Modified | Add persisted global-classification predicate. |
| `tests/Feature/Admin/AuditLogTest.php` | Modified | Correct mixed-classification rendered-output assertions. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Legacy unclassified rows disappear | Low | Intended fail-closed behavior; do not reclassify them. |
| Broadened fix changes protected surfaces | Low | Limit production change to the one query predicate. |

## Rollback Plan

Revert the predicate and its corrected test assertions together. No schema, captured data, route, policy, or UI state requires rollback.

## Dependencies

- Existing `activity_log.organizer_id` and `activity_log.is_global` columns.
- Existing global-audit authorization and DTO projection.

## Success Criteria

- [ ] Only rows matching `organizer_id IS NULL AND is_global = true` render for `super_admin`.
- [ ] Tenant and unclassified rows are absent, including with an active tenant context.
- [ ] Existing authorization and safe projection regressions remain green.
