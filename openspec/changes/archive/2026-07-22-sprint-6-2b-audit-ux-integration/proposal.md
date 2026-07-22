# Proposal: Sprint 6.2b Audit UX Integration

## Intent

Evolve the existing protected Global Audit Log into a scan-friendly platform-control screen without broadening data exposure or access. Sprint 6.2a reconciliation of the canonical filter `organizer_id IS NULL AND is_global = true` is a blocking dependency; filters and counts MUST NOT ship before it is verified.

## Scope

### In Scope
- Contextual control header, immutable/read-only cue, and filtered-result count.
- Fixed allowlisted server-side filters: `log_name`, `event`, and bounded date range; active chips and reset.
- Pagination reset on every filter mutation; count sourced from the filtered bounded paginator.
- Scan-friendly desktop rows, mobile activity cards, and safe loading, empty, and error states.
- Preserve exact `super_admin` access, component reauthorization, and safe DTO-only projection.

### Out of Scope
- Schema, capture-seam, or backfill changes; tenant or unclassified views.
- Exports, charts, payload/full-text search, generic table abstraction, or navigation restructuring.
- Changes to Sprint 6.2a artifacts or implementation.

## Capabilities

### New Capabilities
- `audit-ux-integration`: Responsive, read-only global-audit controls and presentation over the established visibility boundary.

### Modified Capabilities
- None.

## Approach

Enhance `/admin/audit-logs` in place, reusing platform-report visual conventions while retaining its stricter authorization boundary. Apply filters after the canonical global predicate, validate a closed value set, and render only safe scalar DTO fields; never query or expose payload JSON.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `resources/views/livewire/admin/audit-log.blade.php` | Modified | Controls, responsive rows/cards, safe states. |
| `app/ViewModels/Admin/AuditLogViewModel.php` | Modified | Allowlisted filters, canonical query, filtered count. |
| `app/ViewModels/Admin/AuditLogEntryDto.php` | Modified | Display-safe fields only if needed. |
| `tests/Feature/Admin/AuditLogTest.php` | Modified | Filter, state, access, and redaction coverage. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Incorrect upstream classification | Medium | Block until 6.2a exact predicate is verified. |
| Sensitive-data or access drift | Medium | DTO projection; exact `super_admin` tests for page and updates. |
| Scope/budget creep | Medium | Fixed filters only; single PR target: 520–720 lines under 800. |

## Rollback Plan

Revert the page, ViewModel/DTO, and focused tests together. No schema or persisted-state rollback is required.

## Dependencies

- Verified Sprint 6.2a canonical global filtering: `organizer_id IS NULL AND is_global = true`.
- Existing exact `super_admin` route/component boundary and safe audit DTO seam.

## Success Criteria

- [ ] Only verified global rows are filterable and counted.
- [ ] `super_admin` access and DTO redaction remain fail-closed.
- [ ] Desktop and mobile provide readable, safe loading/empty/error states.
- [ ] Focused implementation remains within the 800-line single-PR budget.
