## Exploration: Sprint 6.2b Audit UX Integration

### Current State

The existing `admin.audit-log` Volt page is already a protected, read-only global audit surface. It has pagination, a loading skeleton, empty/error states, safe DTO projection, and an exact `super_admin` policy/route boundary. Its current UI is a standalone desktop-first table with no filters, result count, active-filter summary, or mobile stacked presentation.

Platform reports already establish the visual language to reuse: contextual headers, responsive spacing, bordered cards, existing form controls, dark mode, and domain-specific tables. The sidebar already places both Platform Reports and Global Audit Log within Platform Administration, so integration should improve the audit page's contextual relationship with platform control rather than add a second navigation taxonomy.

The current ViewModel is not yet eligible for UX filter/count work: it queries only `organizer_id IS NULL`, while the accepted upstream classification contract is `organizer_id IS NULL AND is_global = true`. This 6.2a reconciliation is a hard prerequisite.

### Affected Areas

- `resources/views/livewire/admin/audit-log.blade.php` — extend the existing Volt screen with a reporting/control header, immutable read-only cue, safe filter controls and chips, responsive rows, and polished state containers.
- `app/ViewModels/Admin/AuditLogViewModel.php` — accept a small validated filter value set, apply it server-side after the canonical classification boundary, and return a bounded paginator whose total supplies the result count.
- `app/ViewModels/Admin/AuditLogEntryDto.php` — retain the safe scalar projection; only add display-safe fields if the responsive row needs them.
- `tests/Feature/Admin/AuditLogTest.php` — preserve authorization/redaction coverage and add focused contracts for filters, reset, count, responsive markup, and safe states.
- `resources/views/livewire/admin/reports/platform-hub.blade.php` — reference-only visual and interaction pattern; do not couple audit aggregation into financial reporting or add charts.
- `resources/views/livewire/organizers/events-table.blade.php` — reference-only pattern for domain-owned Livewire filters, resets, pagination, and filter UI; no generic table abstraction.
- `resources/views/components/navigation/sidebar.blade.php` — likely unchanged; evaluate a minimal label/grouping adjustment only if it materially clarifies the existing Platform Administration route hierarchy.
- `openspec/changes/sprint-6-2a-audit-visibility/` — upstream dependency only; its artifacts and implementation are not modified by this change.

### Approaches

1. **Focused enhancement of the existing audit page** — Keep `/admin/audit-logs`, its policy, safe ViewModel/DTO seam, and Platform Administration location. Add a contextual platform-control header, immutable/read-only cue, total count, allowlisted server-side filters (`log_name`, `event`, and bounded date range), active-filter chips/reset, desktop table plus mobile activity cards, and explicit loading/empty/error states.
   - Pros: Reuses proven authorization and presentation seams; keeps data exposure narrow; meets the approved UX direction without new navigation or framework work; feasible within one reviewable slice.
   - Cons: The Volt single-file component and its feature test are already sizable; only a small filter set fits the budget.
   - Effort: Medium (estimated 520–720 authored changed lines, including tests).

2. **Embed audit data into the Platform Report Center** — Add the audit list and filters to `admin.reports.platform-hub`.
   - Pros: Makes the reporting/control relationship visually explicit.
   - Cons: Weakens the exact `super_admin` boundary because Platform Reports also admits `platform_admin`; mixes financial aggregation with sensitive activity evidence; increases coupling and review scope.
   - Effort: High.

3. **Create reusable generic audit/table/filter components first** — Abstract common controls before enhancing the page.
   - Pros: Could reduce later duplication.
   - Cons: Conflicts with the established domain-table convention and adds an unproven abstraction solely for one screen; exceeds the bounded UX slice.
   - Effort: High.

### Recommendation

Adopt **Approach 1** after Sprint 6.2a reconciles the canonical global classification query. Keep the sensitive audit route and exact `super_admin` gate intact, but present it as a platform-control screen through the same header, cards, form controls, dark-mode treatment, and responsive conventions used by reports. The implementation MUST filter on the server with a fixed allowlist, reset pagination whenever a filter changes, render only the existing safe DTO fields, and treat the paginator total as the filtered result count. Mobile MUST use stacked activity rows rather than a horizontally dependent table; desktop MAY retain the scan-friendly table.

Bounded scope: no schema/capture/backfill changes; no tenant or unclassified activity; no export, write actions, charts, full-text payload search, generic table framework, new sidebar taxonomy, or changes to `sprint-6-2a-audit-visibility`. The forecast remains under the 800-line single-PR budget, with a medium risk because the existing 489-line audit test should be extended selectively rather than rewritten.

### Risks

- **Upstream classification debt:** counts and filters would make incorrect data more visible if 6.2a remains on `organizer_id IS NULL` alone. Block implementation until `organizer_id IS NULL AND is_global = true` is verified.
- **Sensitive-data leakage:** filter and responsive-card work must continue to use the ViewModel/DTO projection; never search or render JSON payloads, attribute changes, or raw models.
- **Authorization drift:** `platform_admin` may access Platform Reports but MUST remain excluded from audit page loads and Livewire updates.
- **State inconsistency:** every allowed filter mutation and reset must reset pagination; date validation and a fixed query allowlist prevent invalid or unbounded requests.
- **Budget creep:** adding actor/resource lookup filters, exports, charts, or shared abstractions would exceed the bounded UX slice and should be deferred.

### Ready for Proposal

Yes — once the proposal declares Sprint 6.2a classification reconciliation as an explicit dependency and limits implementation to the focused existing-page enhancement above. The maintainer can use a single PR; no PR workflow or chained split is recommended at the 800-line budget.
