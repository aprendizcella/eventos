# Design: Sprint 6.2a Audit Visibility

## Technical Approach

We will implement a read-only global Audit Log interface using Livewire Volt, strictly restricted to users with the exact `super_admin` role. This UI exclusively surfaces global system events by querying persisted schema classifications based on the existing `organizer_id` and `is_global` columns on the `activity_log` table. 

This slice does not introduce any schema migrations, capture-seam modifications, or historical backfill operations. It enforces a strict read/query boundary where only rows matching the canonical global classification (`organizer_id IS NULL AND is_global = true`) are returned.

## Technical Debt Note

Keep the filter contract explicit (`organizer_id IS NULL AND is_global = true`) until the remaining verification/archive evidence is completed. Do not describe this slice as fully verified or archived yet.

## Architecture Decisions

### Decision: Canonical Global State Query

**Choice**: Hardcode the global classification filter (`organizer_id IS NULL AND is_global = true`) in the query builder.
**Alternatives considered**: Inferring from current request tenant or falling back to `properties` JSON.
**Rationale**: Adhering strictly to the persisted schema columns ensures correct tenant isolation and prevents any runtime context mismatches from broadening access. Unclassified records (`organizer_id IS NULL AND is_global = false`) and tenant records are strictly excluded from this query.

### Decision: Explicit Safe Projection

**Choice**: Use a dedicated `GlobalAuditViewModel` to map raw `activity_log` models into safe scalar DTOs for the UI.
**Alternatives considered**: Passing Eloquent models directly to the Livewire view.
**Rationale**: Direct model usage risks exposing sensitive `properties` and `attribute_changes` payloads to the frontend state. The ViewModel guarantees that only `id`, `log_name`, `event`, `description`, subject identity, causer identity, and `created_at` are projected. Missing identities will safely fallback to "Unknown", and all values will be escaped.

### Decision: Fail-Closed Authorization

**Choice**: Enforce exact `super_admin` role matching at the route and Livewire/Volt update boundaries.
**Alternatives considered**: Standard policy based on broad capabilities.
**Rationale**: This explicit constraint ensures platform administrators and other roles are denied access (fail-closed). Any query failures or unauthorized access attempts result in a safe error state without partial data or exception details.

### Decision: Deterministic Bounded Navigation

**Choice**: Pagination with a bounded default/max size and deterministic ordering (`created_at DESC, id DESC`).
**Alternatives considered**: Default pagination on just `created_at`.
**Rationale**: `created_at` is not strictly unique. Adding `id DESC` guarantees stable cursor/page behavior without skipping or duplicating records across pages.

## Data Flow

    [Livewire Volt Component] ──(requests page)──→ [Route / Component Auth Check (super_admin)]
               │                                            │ (If authorized)
               ▼                                            ▼
      [GlobalAuditViewModel] ──(queries)──→ [activity_log (filter: organizer_id IS NULL AND is_global = true)]
               │
               ▼ (Maps to safe DTOs, stripping payloads)
       [Rendered HTML UI]

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `routes/web.php` | Modify | Add `Route::get('/admin/audit-logs', ...)` or Livewire route protected by exact `super_admin` checks. |
| `resources/views/livewire/admin/audit-log.blade.php` | Create | Volt SFC for the read-only, paginated global audit table. Includes loading, empty, and safe error states. |
| `app/ViewModels/Admin/AuditLogViewModel.php` | Create | Projects raw activities into safe UI structures (`properties` and `attribute_changes` strictly omitted) and manages the query boundary. |
| `tests/Feature/Admin/AuditLogTest.php` | Create | Pest tests covering exact authorization, canonical state querying, payload redaction, and deterministic ordering. |

## Interfaces / Contracts

```php
readonly class AuditLogEntryDto {
    public function __construct(
        public int $id,
        public string $logName,
        public string $event,
        public string $description,
        public string $actorName,
        public string $resourceName,
        public string $timestamp
    ) {}
}
```

## Resiliency and Observability

- **Tenant Context Preservation**: The global query executes without mutating or depending on the active tenant context. 
- **Redacted Observability**: When access is denied, rows are excluded, or the query fails, redacted structured logs are emitted. These logs include event identifiers and outcomes but strictly omit any payloads or secrets.
- **Fail-Closed States**: Loading and empty states are explicit. Query failures produce a safe error UI with no exception leakage.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit/Feature | Safe Projection | Verify `GlobalAuditViewModel` returns DTOs without `properties`/`attribute_changes`, escapes strings, and handles missing identities gracefully. |
| Feature | Authorization | Pest tests asserting success for `super_admin`, and 403/fail-closed for platform admins, guests, and other roles. |
| Feature | Query Boundary | Verify only `organizer_id IS NULL AND is_global = true` rows are returned. Assert tenant and unclassified rows are absent. |
| Feature | Pagination | Assert deterministic ordering using `created_at DESC, id DESC`. |

## Threat Matrix

- **Data Leakage (Properties/Changes)**: High risk mitigated by strict ViewModel projection omitting payload JSON.
- **Authorization Bypass**: Medium risk mitigated by exact `super_admin` role checks and fail-closed component logic.
- **Querying Unclassified Data**: Low risk mitigated by hardcoded explicit classification filter.

## Rollback Boundaries

No schema or state changes occur. Rollback is strictly code-level:
1. Revert `routes/web.php` addition.
2. Delete the Volt component, ViewModel, and Feature tests.
