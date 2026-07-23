# Proposal: Audit Component Consistency

## Intent

Make the protected Global Audit Log visually and structurally consistent with established platform report and event-detail patterns, without changing the verified Sprint 6.2b audit behavior, data boundary, or access model.

## Scope

### In Scope
- Add `declare(strict_types=1);` to the Volt PHP section if compilation and focused tests confirm compatibility.
- Replace the existing audit filter selects, date fields, Apply action, and conditional Reset action with `x-form.select`, `x-form.date`, and `x-ui.button`.
- Align the existing header, filter card, and result-card hierarchy with the reports/event-detail spacing, responsive header, badge, and bordered-card conventions.
- Preserve and, where stable, assert component rendering while retaining the focused audit behavior/security suite.

### Out of Scope
- Query, DTO, ViewModel, validation, filter-state, pagination, route, policy, authorization, navigation, or responsive-record changes.
- New components, generic abstractions, branding work, exports, charts, payload search, or changes to archived artifacts.

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- None. This is a presentation-only refactor; existing audit requirements remain unchanged.

## Approach

Modify only the audit Volt view and its focused feature test. Keep the deferred draft/applied state and existing `wire:submit`, `wire:click`, validation keys, IDs, and desktop/mobile containers intact. Feed the existing allowlists and draft property names to the form primitives. Preserve compact inline actions by overriding `x-ui.button`'s default full-width class. Use only the minimum card/header nesting changes needed to match the established references.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `resources/views/livewire/admin/audit-log.blade.php` | Modified | Strict types when compatible; primitive substitution and presentation hierarchy. |
| `tests/Feature/Admin/AuditLogTest.php` | Modified | Narrow rendering regressions alongside existing behavior/security contracts. |
| `resources/views/components/form/{date,select}.blade.php` | Reference | Existing Livewire-compatible input primitives. |
| `resources/views/components/ui/button.blade.php` | Reference | Reused action primitive; compact width override required. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Alpine date forwarding changes filter input behavior | Medium | Preserve `wire:model` names and cover paired-date submission with the focused Volt suite. |
| Button becomes full-width | Medium | Pass an explicit compact-width class. |
| Markup refactor breaks stable hooks or responsive states | Low | Retain current IDs, error keys, state containers, and record markup. |
| Report styling implies report permissions | Low | Preserve exact `super_admin` route and component authorization. |

## Rollback Plan

Revert the audit view and paired focused tests together. No schema, persisted state, route, policy, or data rollback is required.

## Dependencies

- Archived Sprint 6.2b audit contracts remain the behavioral baseline.
- Existing form/date/select/button primitives and report/event-detail visual conventions.

## Success Criteria

- [x] Audit controls use the established date, select, and button primitives without changing filter behavior or state.
- [x] Header and cards match existing platform conventions while desktop/mobile, loading, empty, and error contracts remain intact.
- [x] Exact `super_admin` access, canonical global query, DTO-safe projection, routes, and pagination behavior remain unchanged.
- [x] Focused audit tests pass; delivery remains below the approved 800-line single-delivery budget.
