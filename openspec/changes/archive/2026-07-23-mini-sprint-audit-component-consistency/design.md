# Design: Audit Component Consistency

## Technical Approach

Refactor only `admin.audit-log` presentation in place. Retain its class-based Volt lifecycle, draft-versus-applied filter state, methods, query/ViewModel seam, route, policy, safe DTO rendering, and responsive record hooks. Reuse the existing report filter primitives and event-detail card vocabulary; do not copy report authorization or behavior.

## Architecture Decisions

| Decision | Choice | Alternatives / rationale |
|---|---|---|
| Date-state bridge | Render each `x-form.date` with the existing draft property as both `:value` and `wire:model`; retain `wire:submit="applyFilters"`. The component's visible Alpine text input receives `wire:model`, and its picker dispatches a bubbling `input` event. | Native date inputs would avoid Alpine but violate the reuse requirement. Supplying `:value` prevents the picker from initializing independently of hydrated draft state. |
| Select contracts | Convert indexed DTO allowlists to value-to-label maps and pass explicit labels: `label="Log name"` for `draftLogName` and `label="Event"` for `draftEvent`. | Passing indexed constants would submit numeric keys; omitting `label` would remove the rendered labels because `x-form.select` renders them only when supplied. |
| Compact actions | Use `x-ui.button` for Apply and Reset with `class="!w-auto"`; retain `type="button"` and `wire:click="resetFilters"` on Reset. | The shared button defaults to `w-full`; raw buttons or a component change are unnecessary. Existing event settings establishes `!w-auto` as the local override. |
| Presentation scope | Add `declare(strict_types=1);` and make only header/filter/result-card nesting and utility adjustments that match report/event-detail conventions. Preserve copy, IDs, error keys, loading/error/empty branches, `md` desktop/mobile containers, and `wire:key` values. | A broad restyle or generic abstraction creates DOM and behavior risk without satisfying an additional requirement. |

## Data Flow

```text
Volt draft properties
  -> x-form.select / x-form.date (wire:model; date picker emits input)
  -> wire:submit applyFilters()
  -> existing validation and atomic promotion to applied properties
  -> existing AuditLogFilterDto -> ViewModel -> safe paginator/DTOs
  -> existing chips, count, table/cards, or fail-closed state
```

Reset remains a direct Livewire action: it clears draft and applied properties, validation, and pagination. No component may alter query parameters, authorization, or DTO fields.

## File Changes

| File | Action | Description |
|---|---|---|
| `resources/views/livewire/admin/audit-log.blade.php` | Modify | Strict declaration; shared select/date/button usage; minimal report/event-detail-aligned hierarchy. |
| `tests/Feature/Admin/AuditLogTest.php` | Modify | Add narrow rendered-control and compact-action assertions while retaining behavioral/security coverage. |
| `resources/views/components/form/date.blade.php` | Reference | Alpine date picker and `wire:model` forwarding; unchanged. |
| `resources/views/components/form/select.blade.php` | Reference | Key/value option API and shared validation rendering; unchanged. |
| `resources/views/components/ui/button.blade.php` | Reference | Default full-width behavior overridden locally; unchanged. |

No route, DTO, ViewModel, policy, migration, navigation, component, or archived artifact changes.

## Interfaces / Contracts

```blade
<x-form.date id="draftDateFrom" name="draftDateFrom"
    label="From" :value="$draftDateFrom" wire:model="draftDateFrom" />

<x-form.select id="draftLogName" name="draftLogName"
    label="Log name"
    :options="array_combine(AuditLogFilterDto::LOG_NAMES, AuditLogFilterDto::LOG_NAMES)"
    placeholder="All logs" wire:model="draftLogName" />

<x-form.select id="draftEvent" name="draftEvent"
    label="Event"
    :options="array_combine(AuditLogFilterDto::EVENTS, AuditLogFilterDto::EVENTS)"
    placeholder="All events" wire:model="draftEvent" />

<x-ui.button type="submit" class="!w-auto">Apply filters</x-ui.button>
```

Dates retain their explicit labels and values. Explicit IDs are deterministic control identifiers; all existing record/state IDs remain unchanged.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Feature / Volt | Resolved controls retain draft `wire:model`, explicit `Log name` and `Event` labels, placeholders, allowlisted values, deterministic IDs, and compact Apply/conditional Reset markup. | Add stable rendered-HTML assertions against compiled output, never Blade tags. Assert both visible labels and both select IDs/bindings/options. |
| Feature / Volt | Immutable cue and report-aligned hierarchy | With one global row seeded, assert `Global Audit Logs`, `Read-only audit trail`, and `Immutable records`; assert their order before the filter-card labels and matching-record count. Assert the rendered responsive-header (`sm:flex-row`), filter-card (`rounded-xl border ... shadow-sm`), and desktop result-card container (`audit-log-desktop-records` plus the bordered-card classes). |
| Feature / Volt | Retained active-filter feedback | Set and apply an allowlisted log/event filter, then assert the active-filter region (`aria-label="Active audit filters"`) and its `Log: auth` / `Event: login` chips while the filtered count and row remain visible. Reset and assert the region/chips are absent and pagination is page one. |
| Feature / Volt | Date and filter behavior | Retain paired inclusive-date, invalid-draft/no-broadening, apply/reset pagination, chips, count, empty/loading/error, and responsive-container tests. These prove strict-types lifecycle and server state remain compatible. |
| Feature / security | Existing boundary | Run the focused suite unchanged for exact `super_admin`, reauthorization, global predicate, redaction, deterministic pagination, and excluded controls. |
| Browser | Not planned | No browser test is required for this bounded refactor; add one only if the shared Alpine picker fails to forward a selected date in implementation. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary is modified.

## Migration / Rollout

No migration required. One presentation-only delivery is estimated at 80–160 authored lines, below the approved 800-line single-delivery budget. Roll back the Volt view and paired assertions together.

## Open Questions

None.
