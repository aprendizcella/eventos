## Exploration: Mini Sprint Audit Component Consistency

### Current State

`admin.audit-log` is a class-based Volt page that already preserves the closed Sprint 6.2b behavior: an exact `super_admin` route and component authorization boundary, the canonical `organizer_id IS NULL AND is_global = true` projection, allowlisted deferred filters, pagination reset, safe DTO-only rows, responsive table/cards, and focused Pest coverage. Its filter controls and action buttons are raw HTML, and its header/card hierarchy is close to, but not expressed through, the reusable patterns used by `/admin/reports` and organizer event pages.

`admin.reports.platform-hub` and organizer report pages use `<x-form.date>`, `<x-form.select>`, and `<x-ui.button>` in a card-based filter form. The organizer event detail page establishes the matching `space-y-6`, responsive header, badge, and bordered-card hierarchy. The existing form components forward `wire:model` to their native interactive elements and render labels/errors; their use is therefore compatible with the audit page's existing draft scalar state and `applyFilters()` action. `x-ui.button` defaults to full width, so inline audit actions must explicitly retain their current compact width via its supported attribute merging.

### Affected Areas

- `resources/views/livewire/admin/audit-log.blade.php` — sole implementation target: add `declare(strict_types=1);`, replace compatible raw selects/dates/actions with existing primitives, and align presentation-only header/card nesting.
- `tests/Feature/Admin/AuditLogTest.php` — preserve behavior/security contracts and add narrow rendered-markup/component-usage assertions where stable.
- `resources/views/components/form/date.blade.php` — reference only; forwards `wire:model` to its readonly text input while Alpine dispatches the input event.
- `resources/views/components/form/select.blade.php` — reference only; forwards `wire:model`, labels, options, placeholder, and errors to the native select.
- `resources/views/components/ui/button.blade.php` — reference only; forwards classes/attributes but defaults to `w-full`, requiring an inline override for audit actions.
- `resources/views/livewire/admin/reports/platform-hub.blade.php` and `resources/views/organizers/events/show.blade.php` — visual/component references only; neither should be changed.

### Approaches

1. **Targeted primitive substitution** — Keep all audit state, methods, query seams, route/policy, responsive record markup, and navigation unchanged. Replace the two selects, two dates, submit action, and conditional reset action with the existing form/button components; add strict types to the PHP section; make only structural/card-class adjustments needed to match the established header hierarchy.
   - Pros: Reuses proven components, confines the change to presentation, and protects every closed audit contract.
   - Cons: The date picker adds Alpine calendar behavior, so date/filter interaction must be covered by the existing Volt feature suite.
   - Effort: Low (estimated 80–160 authored changed lines including tests).

2. **Broader audit-page restyle** — Rework filter, result, and responsive-record containers to resemble reports and organizer details more extensively.
   - Pros: More visual convergence.
   - Cons: Risks changing tested DOM hooks, mobile records, loading/empty/error states, and the narrowly approved audit UX; offers no behavioral value.
   - Effort: Medium.

### Recommendation

Adopt **Approach 1**. Make a presentation-only component-consistency pass in `admin.audit-log`: declare strict types; use `<x-form.select>` with the existing allowlists and empty placeholders; use `<x-form.date>` with the same draft property names; and use `<x-ui.button>` with an explicit non-full-width class for Apply and Reset. Retain the current `wire:submit`, deferred draft/applied state distinction, validation keys, `wire:click`, IDs, responsive records, copy, authorization, safe projection, and route/sidebar structure. Reuse the reports/event-detail spacing and card hierarchy only where it does not move or remove existing state containers.

Tests should first preserve all existing audit behavior. Add only narrow render-level assertions that the page resolves the form primitives/strict declaration if they are robust; interaction tests must continue to prove date bounds, invalid drafts retaining prior applied filters, reset/page reset, exact authorization, safe redaction, count, and responsive states. No browser test is required unless the existing Volt test exposes an Alpine/date-forwarding regression.

### Risks

- `<x-form.date>` is a custom Alpine picker rather than a native `type="date"`; preserve `wire:model` on the forwarded text input and verify paired-date submissions through `Volt::test()`.
- `<x-ui.button>` defaults to `w-full`; omit an inline class override and the current compact filter layout will regress.
- Replacing DOM markup can accidentally remove IDs/classes covered by audit tests or alter error placement; retain stable hooks and validation property names.
- Do not infer visual consistency as permission consistency: `/admin/reports` permits `platform_admin`, while audit MUST remain exact `super_admin` only.

### Ready for Proposal

Yes — scoped to one Volt view and its focused feature test, safely below the maintainer-approved 800-line single-delivery budget. Proposal and implementation MUST exclude query/DTO/ViewModel changes, route/policy/navigation changes, HI.EVENTS branding, generic abstractions, and all archived artifacts.
