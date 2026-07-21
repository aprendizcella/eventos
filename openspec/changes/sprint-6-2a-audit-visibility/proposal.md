# Proposal: Sprint 6.2a Audit Visibility

## Intent

Implement a read-only global audit visibility UI for super administrators, built entirely on the existing `activity_log` foundation. This slice exclusively surfaces global system events by querying persisted schema classifications. It deliberately avoids creating new migrations, capture-seam modifications, or historical backfill operations.

## Technical Debt

- Sprint 6.2 still has a visibility-contract inconsistency between implementation, tests, and docs.
- The intended rule is explicit global rows only: `organizer_id IS NULL AND is_global = true`.
- Status and verification evidence still need final reconciliation before this slice can be treated as fully closed.

## Scope

### In Scope
- **Global Audit UI**: A read-only Livewire/Volt interface surfacing global activity logs.
- **Read-Time Visibility**: Queries based strictly on the canonical global state (`organizer_id IS NULL AND is_global = true`).
- **Authorization**: Exact matching for `super_admin` role. Platform admins and other roles are denied.
- **Data Exposure Constraints**: Explicitly exclude `properties` and `attribute_changes` payloads from the UI to prevent sensitive data leakage.
- **Product Behaviors**: Pagination, deterministic ordering (latest first), error handling, and observability for the read model.

### Out of Scope
- **Schema Changes**: No database migrations or `activity_log` table modifications.
- **Capture Seam Work**: No changes to how activity events are logged or classified.
- **Historical Backfill**: Retroactive classification of legacy logs is explicitly deferred to a separate future change.
- **Tenant Audit UI**: Only the global UI is built in this slice; tenant-specific logs (`organizer_id IS NOT NULL AND is_global = false`) are not surfaced here.
- **Unclassified Logs**: Records with `organizer_id IS NULL AND is_global = false` are strictly excluded from the UI.

## Capabilities

> This section is the CONTRACT between proposal and specs phases.
> The sdd-spec agent reads this to know exactly which spec files to create or update.

### New Capabilities
- `global-audit-visibility`: Read-only UI surfacing global activity logs exclusively to super administrators, enforcing strict query boundaries, pagination, and data exposure limits.

### Modified Capabilities
- None

## Approach

1. **Dependency on Existing Foundation**: Rely entirely on the pre-existing `organizer_id` and `is_global` columns in `activity_log`.
2. **Canonical State Querying**: The UI read model will filter records exactly by `organizer_id IS NULL AND is_global = true`. It will never infer ownership or fallback to the current request tenant.
3. **Fail-Closed Authorization**: Access is strictly limited to users with the `super_admin` role. The system will fail closed for any other roles (including platform admins).
4. **Data Redaction**: The UI components will render event subjects, causers, descriptions, and timestamps, but will strictly omit the JSON `properties` and `attribute_changes` fields to prevent PII/sensitive data leaks.
5. **Robust Read Model**: Implement standard pagination and deterministic ordering to ensure performance and stable UI states, backed by comprehensive product-level test coverage.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | New route for the global audit UI. |
| `resources/views/livewire/admin/audit-log.blade.php` | New | Read-only Volt/Livewire components for the global audit log. |
| `tests/Feature/Admin/AuditLogTest.php` | New | Tests verifying authorization, canonical state querying, data redaction, and pagination. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| **Data Leakage (Properties/Changes)** | High | Strictly enforce exclusion of JSON payload fields at the view/ViewModel layer. |
| **Authorization Bypass** | Medium | Use explicit role checks for `super_admin` and test fail-closed behavior for platform admins and tenants. |
| **Querying Unclassified Data** | Low | Hardcode the exact canonical classification filter (`organizer_id IS NULL AND is_global = true`) in the read query. |

## Rollback Plan

1. Revert the route addition in `routes/web.php`.
2. Remove the Livewire/Volt components and related test files.
3. Since no schema or state changes occur, rollback is purely code-level.

## Dependencies

- Existing `activity_log` table with `organizer_id` and `is_global` columns.
- Existing authorization framework to verify the `super_admin` role.

## Success Criteria

- [ ] A super administrator can access and view paginated global audit logs.
- [ ] Platform administrators and other roles are denied access (fail-closed).
- [ ] The UI displays only global logs (`organizer_id IS NULL AND is_global = true`), strictly excluding tenant and unclassified data.
- [ ] `properties` and `attribute_changes` are not exposed in the UI.
- [ ] Product-level tests verify authorization, deterministic ordering, and data redaction.
