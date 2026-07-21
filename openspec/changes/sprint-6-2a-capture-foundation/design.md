# Design: Sprint 6.2a Audit Visibility - Capture Foundation

## Technical Approach

We will extend the `activity_log` table to include `organizer_id` and `is_global`. To guarantee strict ownership assignment before persistence (even when buffered), we will use a custom `LogActivityAction` seam. This action intercepts all logs, reads explicit metadata or the current `Organizer` tenant, enforces the invariant contract, and sets the classification columns. 

## Architecture Decisions

### Decision: FK Deletion Policy
**Choice**: `restrictOnDelete()` for the `organizer_id` foreign key.
**Alternatives considered**: `nullOnDelete` or `cascadeOnDelete`.
**Rationale**: The spec requires immutable ownership. `nullOnDelete` destroys attribution, failing the audit invariant. Since `Organizer` uses `SoftDeletes`, soft deleting an organizer preserves the logs. If a physical deletion of an organizer is attempted, `restrictOnDelete()` will refuse it, explicitly protecting the audit history from accidental destruction.

### Decision: Capture Seam Implementation
**Choice**: Override `Spatie\Activitylog\Actions\LogActivityAction` via `config/activitylog.php`.
**Alternatives considered**: Eloquent `creating` event on the `Activity` model.
**Rationale**: Model events are bypassed when Spatie Activitylog's buffering is enabled (it uses a bulk `insert` query). `LogActivityAction` is executed by the Spatie logger *before* building the model attributes for both immediate and buffered insertions, guaranteeing total coverage.

### Decision: Invariant Constraint & Migration Strategy
**Choice**: Raw SQL `ALTER TABLE` for MariaDB and table rebuild for SQLite.
**Alternatives considered**: Relying solely on application-level logic.
**Rationale**: The DB must physically reject contradictions (`is_global = 1` AND `organizer_id IS NOT NULL`). Laravel 11's Blueprint lacks a cross-DB `check()` method. We will execute a raw SQL constraint for MariaDB and perform a temporary table swap for SQLite to ensure tests cover the physical DB rejection.

### Decision: Explicit Classification API
**Choice**: Inject explicit metadata via `activity()->withProperties(['is_global' => true])` or `['organizer_id' => 7]`.
**Alternatives considered**: Create a dedicated `Audit::global()` facade wrapper.
**Rationale**: This fits natively into Spatie's API without forcing the caller to rewrite existing syntax. The custom `LogActivityAction` will extract these specific keys from properties, apply them directly to the DB columns, and strip them from the payload so they don't pollute the JSON `properties` column.

## Data Flow

    Application Code / Queue Jobs
          │
          ▼
    activity()->log('message')
          │
          ▼
    App\Actions\Audit\LogActivityAction (Capture Seam)
    │  1. Check explicit properties (is_global, organizer_id)
    │  2. If none, read current Tenant (app(IsTenant::class)->current())
    │  3. Validate invariants (Global XOR Organizer, Conflict Rejection)
    │  4. Strip metadata from JSON payload
    │  5. Assign columns to Activity Model
          │
          ▼
    Spatie Activity Logger (Buffer or Immediate Insert)
          │
          ▼
    activity_log table (MariaDB with FK & CHECK constraints)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/..._add_classification_to_activity_log.php` | Create | Adds `organizer_id`, `is_global`, FK restrict, and DB CHECK constraint. |
| `config/activitylog.php` | Modify | Update `actions.log_activity` to point to our custom action. |
| `app/Actions/Audit/LogActivityAction.php` | Create | Intercepts logs, resolves tenant, enforces invariant, strips metadata. |
| `tests/Feature/Audit/ActivityCaptureTest.php` | Create | Tests invariant rejection, context propagation, buffering, and isolation. |

## Interfaces / Contracts

The `LogActivityAction` will enforce this exact truth table:

| Explicit `is_global` | Explicit `organizer_id` | Current Tenant | Result DB (`organizer_id`, `is_global`) |
|----------------------|-------------------------|----------------|-----------------------------------------|
| `true`                 | `null`                    | (ignored)      | `NULL`, `true`                          |
| `null`/`false`           | `7`                       | (ignored)      | `7`, `false`                            |
| `true`                 | `7`                       | (ignored)      | **Exception Thrown**                    |
| `null`/`false`           | `null`                    | `7`              | `7`, `false`                            |
| `null`/`false`           | `null`                    | `null`           | `NULL`, `false` (Unclassified)          |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (DB) | Schema Constraints | Test MariaDB and SQLite physical constraints reject `is_global=1` + `organizer_id=7`. |
| Integration | Capture Seam | Test that explicit properties properly route to columns and are stripped from JSON. |
| Integration | Queue Context | Dispatch `TenantAware` and standard Jobs; verify logs get correct `organizer_id` or default to unclassified. |
| E2E / Boundary | Buffering / Isolation | Enable Spatie buffering. Log as Tenant A, clear context, log as Global. Verify exact rows in DB without leakage. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No data migration required for the foundation phase. 
Existing rows will receive `organizer_id = NULL` and `is_global = false` (by default), which accurately reflects their current unclassified state. 
**Rollback Plan**: The `down()` migration method must warn the user that dropping the `organizer_id` and `is_global` columns will permanently destroy all classification data generated since the migration.

## Open Questions

- None.