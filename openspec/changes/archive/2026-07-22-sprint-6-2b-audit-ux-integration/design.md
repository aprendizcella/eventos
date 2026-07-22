# Design: Sprint 6.2b Audit UX Integration

## Technical Approach

Enhance the existing `admin.audit-log` Volt page in place. It retains its route and exact authorization boundary, but uses the Platform Report Center's responsive card, control, and dark-mode conventions. The ViewModel remains the sole data seam: it applies the verified global predicate first, then typed allowlisted filters, and maps the bounded result to safe DTOs.

**Release gate:** Sprint 6.2a's verified canonical predicate, `organizer_id IS NULL AND is_global = true`, is a blocking prerequisite. Do not begin implementation or release this change until 6.2a verifies it; any upstream correction belongs to the 6.2a change.

## Architecture Decisions

| Decision | Choice | Alternatives / rationale |
|---|---|---|
| Filter contract | Add an immutable `AuditLogFilterDto`; the Volt component keeps only scalar form state and passes a validated DTO to the ViewModel. | Raw request arrays or database-derived values were rejected: closed code-level allowlists prevent arbitrary predicates and make the contract testable. |
| Date bounds | A date filter is optional; when present it requires both ISO dates, `from <= to`, inclusive start/end-of-day semantics, and a maximum 90-day span. Invalid, partial, or injected values fail validation and do not broaden the last safe query. | An open-ended range is rejected because it violates the bounded-filter requirement. |
| Filter application | Deferred form inputs submit through `applyFilters()`; validation atomically promotes draft values to applied values and calls `resetPage()`. `resetFilters()` clears both and calls `resetPage()`. | Live querying each keystroke risks transient invalid ranges and unnecessary queries. |
| Presentation | Render the same safe DTO fields as a desktop table at `md` and stacked activity cards below `md`; render one of loading, empty, or generic error states. | A responsive generic table or a report-hub embed would increase scope or weaken the audit boundary. |

## Data Flow

```text
Livewire draft controls
  -> applyFilters(): validate closed values + 90-day paired dates; reset page
  -> AuditLogFilterDto (applied scalars only)
  -> AuditLogViewModel::getLogs(filter, 10)
  -> Activity query: organizer_id IS NULL AND is_global = true
  -> allowlisted WHERE clauses -> created_at DESC, id DESC -> bounded paginator
  -> AuditLogEntryDto collection + paginator total
  -> header/count, active chips, desktop rows or mobile cards
```

The ViewModel defines the fixed `log_name` and `event` allowlists from the established audit vocabulary; it never obtains filter options from payloads or arbitrary database values. It selects only fields required by the existing DTO, eager-loads causer/subject, and does not select, search, map, or render `properties` or `attribute_changes`. The page and every Livewire render continue to authorize `ActivityPolicy::viewAny`; route middleware remains `role:super_admin`. On a query failure it returns an empty paginator and a generic unavailable message, never partial rows or exception text.

## File Changes

| File | Action | Description |
|---|---|---|
| `app/DataTransferObjects/Admin/AuditLogFilterDto.php` | Create | Immutable validated filter transport object and closed filter/date contract. |
| `app/ViewModels/Admin/AuditLogViewModel.php` | Modify | Canonical predicate, defensive filter application, stable filtered paginator, and existing safe DTO mapping. |
| `resources/views/livewire/admin/audit-log.blade.php` | Modify | Deferred filter form, chips/reset/count, contextual read-only header, responsive records, and safe states. |
| `tests/Feature/Admin/AuditLogTest.php` | Modify | Focused upstream boundary, filter, pagination-reset, count, redaction, authorization, and responsive-markup contracts. |

`routes/web.php`, policies, the DTO entry shape, migrations, and all Sprint 6.2a artifacts remain unchanged.

## Interfaces / Contracts

```php
final readonly class AuditLogFilterDto
{
    public function __construct(
        public ?string $logName,
        public ?string $event,
        public ?CarbonInterface $dateFrom,
        public ?CarbonInterface $dateTo,
    ) {}
}
```

`getLogs(AuditLogFilterDto $filter, int $perPage = 10): LengthAwarePaginator` returns `AuditLogEntryDto` items. The count displayed by the page is exactly `total()` from that paginator.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Feature | Predicate and filters | Seed global, tenant, and unclassified rows; prove only global rows are filtered/counted. Test allowed values, injected values, partial/reversed/over-90-day dates, and no broadening. |
| Feature | Livewire state | Use `Volt::test()` to apply/reset filters from page two and assert page one, chips, paginator total, deterministic equal-timestamp order, loading/empty/generic-error markup, and desktop/mobile containers. |
| Feature | Security | Preserve route and update denial for non-`super_admin`; assert payload secrets, raw JSON, exports, and write controls are absent. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary is modified.

## Migration / Rollout

No migration required. One PR is forecast at 520–720 authored changed lines, within the 800-line budget. Roll back the DTO, ViewModel, Volt view, and focused tests together. Do not begin implementation until the upstream release gate is verified.

## Open Questions

None.
