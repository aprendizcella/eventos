## Exploration: Sprint 6.2a Audit Classification Fix

### Current State

`AuditLogViewModel::queryActivities()` selects safe scalar columns and currently filters only `organizer_id IS NULL`. Consequently, an unclassified row (`organizer_id IS NULL`, `is_global = false`) is returned, mapped to the safe DTO, and rendered. The current feature test explicitly preserves that incorrect behavior by asserting that “Global legacy event” is visible.

The route, policy, Volt authorization checks, and DTO are already aligned with the read-only `super_admin` boundary and safe projection. They do not determine classification and need no change. The existing Sprint 6.2a proposal, specification, design, and tasks all state the intended stricter predicate: `organizer_id IS NULL AND is_global = true`.

### Affected Areas
- `app/ViewModels/Admin/AuditLogViewModel.php` — add the persisted `is_global = true` condition to the sole audit read query.
- `tests/Feature/Admin/AuditLogTest.php` — replace the legacy-row visibility assertion with strict exclusion coverage for global, tenant, and unclassified rows.
- `routes/web.php` — inspected only; the named route and exact `super_admin` middleware remain unchanged.
- `app/Policies/ActivityPolicy.php` — inspected only; component reauthorization remains unchanged.
- `app/ViewModels/Admin/AuditLogEntryDto.php` — inspected only; safe scalar projection remains unchanged.
- `openspec/changes/sprint-6-2a-audit-visibility/{proposal,design,tasks.md,specs/audit-visibility/spec.md}` — inspected only; all already specify the required boundary.

### Approaches
1. **Add the missing query predicate and correct the existing feature case** — append `->where('is_global', true)` to the ViewModel query and make the existing mixed-classification Volt test assert that the unclassified row is absent.
   - Pros: Smallest correction; preserves authorization, pagination, projection, and tenant-context behavior; directly restores the documented contract.
   - Cons: Does not broaden the existing exclusion-observability implementation, which currently only logs tenant rows in the page ID range.
   - Effort: Low.

2. **Extract a reusable global-activity scope and extend exclusion logging** — introduce a model/query scope and revise observability for both excluded classifications.
   - Pros: Centralizes the predicate and can reconcile observability semantics.
   - Cons: Expands a narrowly scoped bug fix, changes more production surfaces, and requires additional tests without a demonstrated second consumer.
   - Effort: Medium.

### Recommendation

Choose Approach 1 with strict TDD:

1. **RED**: Change the existing mixed-classification Volt feature test so it asserts the explicit global row is visible and both the tenant and unclassified rows are absent. Keep the active-tenant-context regression unchanged.
2. Run the focused classification test and confirm it fails because the unclassified row is still rendered.
3. **GREEN**: Add `->where('is_global', true)` immediately after `->whereNull('organizer_id')` in `queryActivities()`.
4. Run the focused classification test, then the full `tests/Feature/Admin/AuditLogTest.php` suite. Run the configured Pint and PHPStan checks before acceptance.

No route, policy, Volt component, DTO, migration, capture seam, or historical-data changes are required. The work is comfortably below the 800-line review budget and should remain a single focused fix.

### Risks
- Existing historical unclassified rows will disappear from the global UI by design; they must not be reclassified implicitly or exposed as global.
- `logExcludedActivities()` currently queries and warns only for tenant rows, so the pre-existing redacted-observability behavior does not report unclassified exclusions. Do not expand this correction unless a separate requirement explicitly reconciles that debt.
- The focused feature assertion verifies rendered behavior; retain the full audit test suite to guard pagination, authorization, safe projection, and tenant-context isolation.

### Ready for Proposal

Yes — create a narrowly scoped proposal/spec that changes only the ViewModel classification predicate and its existing feature coverage. It must retain `organizer_id IS NULL AND is_global = true` as the sole global-audit contract.
