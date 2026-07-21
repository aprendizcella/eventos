# Design: Sprint 6.2a Audit Foundation

## Technical Approach

Introduce a strictly bounded, fail-closed tenant classification model directly into the `activity_log` table. We enforce this through a custom `LogActivityAction` (replacing Spatie's default) to capture `is_global` and `organizer_id` prior to any buffering or database execution, guaranteeing context capture even in queued or buffered scenarios. We strictly partition tenant-scoped logs from global logs and safely exclude unclassifiable legacy data. We introduce DB-level `CHECK` constraints to ensure schema invariants and a strictly scoped SQL query service for visibility.

## Architecture Decisions

### Decision: Central Capture Seam

**Choice**: Extend `Spatie\Activitylog\LogActivityAction` to inject context, replacing the default action in `config/activitylog.php`. We will disable `ActivityBuffer` for this project or ensure our custom action sets the attributes before passing them to the model/buffer.
**Alternatives considered**: Overriding the `Activity` model's `creating` event (rejected: fails on Spatie Activitylog v5 bulk inserts via `ActivityBuffer` which use `query()->insert()`).
**Rationale**: The `LogActivityAction` intercepts the standard `activity()` helper before Spatie decides whether to buffer or insert, providing a guaranteed single entry point. We explicitly do not support raw DB writes to `activity_log` bypassing Spatie.

### Decision: Classification State Machine & Conflict Rules

**Choice**: We define a strict classification state machine:
1.  **Explicit Organizer**: If passed directly via `tap()` or property, use it.
2.  **Explicit Global**: If marked global via `tap()`, use `is_global = true`, `organizer_id = null`.
3.  **Inferred Organizer**: If no explicit context, infer from `Organizer::current()` *at event time only*.
4.  **Unclassified**: If no explicit context and no current Organizer, row becomes `is_global = false, organizer_id = null`.
5.  **Conflict Rejection**: If an event explicitly sets `is_global = true` AND an `organizer_id`, an exception is thrown before insertion.
**Rationale**: Explicit context always wins. Absence of an organizer does not imply global; it implies unclassified.

### Decision: Database Invariants and Soft Deletion

**Choice**: The migration will add a database-level `CHECK` constraint: `is_global = false OR organizer_id IS NULL`. The `organizer_id` will use `nullOnDelete()`.
**Alternatives considered**: Cascade deletion on organizer.
**Rationale**: We must preserve historical audit evidence. `nullOnDelete` safely drops the row to "unclassified" (invisible to tenants) rather than permanently losing data.

### Decision: Visibility Enforcement Boundary

**Choice**: A dedicated `ActivityLogQueryService` or Global Scope that enforces SQL-level boundaries: `super_admin` on team `0` can query global or specific organizers; tenant admins can only query `where('organizer_id', Organizer::current()->id)`.
**Alternatives considered**: Relying solely on `ActivityPolicy`.
**Rationale**: Policies are insufficient for list/index endpoints where data could leak if not scoped in SQL.

### Decision: Backfill Strategy

**Choice**: An idempotent CLI command (`BackfillActivityLogOrganizerCommand`) utilizing deterministic mapping tables mapping `subject_type` to immutable organizer resolution logic. Supports dry-run, chunking, idempotency, and explicit rejection of ambiguous records.
**Rationale**: Safely rectifies legacy records without inferring from mutable states.

## Data Flow

    [ Application / Jobs ]
            │
            ▼
    activity()->tap(fn($a) => $a->is_global = true)->log('System Event')
            │
            ▼
    [ App\Actions\Audit\ClassifyLogActivityAction ]
            ├─ Evaluates explicit markers vs current Organizer
            ├─ Throws on Conflict (Global + Organizer)
            └─ Injects `organizer_id` / `is_global`
            │
            ▼
    [ Spatie ActivityBuffer / DB Insert ]
            │
            ▼
    [ DB: activity_log table ] ◄── Enforces CHECK(is_global = 0 OR organizer_id IS NULL)
            │
            ▼
    [ ActivityLogQueryService ] ◄── Restricts SQL by Role & Team (0 = Global, >0 = Tenant)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/*_add_organizer_context_to_activity_log.php` | Create | Adds `organizer_id` (FK to `organizers`, `nullOnDelete`), `is_global` (bool, default `false`), indexes, and CHECK constraint. |
| `app/Actions/Audit/ClassifyLogActivityAction.php` | Create | Extends Spatie's `LogActivityAction` to apply the classification state machine. |
| `config/activitylog.php` | Modify | Update `actions.log_activity` to point to `ClassifyLogActivityAction`. Disable buffer if needed. |
| `app/Services/Audit/ActivityLogQueryService.php` | Create | Enforces SQL boundaries for `super_admin` vs tenant admins based on Spatie Permission teams. |
| `app/Console/Commands/BackfillActivityLogOrganizerCommand.php` | Create | Idempotent command with deterministic subject mappings, dry-run, and chunking. |
| `tests/Feature/Audit/ClassifyLogActivityActionTest.php` | Create | Tests classification, conflicts, buffering, web/queue contexts, and unclassified safety. |
| `tests/Feature/Audit/ActivityLogQueryServiceTest.php` | Create | Tests strict isolation boundaries (team 0 vs tenant). |
| `tests/Feature/Audit/BackfillActivityLogOrganizerCommandTest.php` | Create | Tests deterministic mapping, skipping ambiguities, idempotency, and dry-run reporting. |

## Interfaces / Contracts

```php
// Explicit Global API
activity()->tap(function(Activity $activity) {
    $activity->is_global = true;
})->log('Global config updated');

// Explicit Organizer API
activity()->tap(function(Activity $activity) use ($organizer) {
    $activity->organizer_id = $organizer->id;
})->log('Organizer specifically targeted');
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Integration | Capture Seam & Conflicts | Validate `ClassifyLogActivityAction` throws `ConflictException` on global+organizer. Verify inferred capture via `Organizer::current()`. |
| DB | Invariants & NullOnDelete | Test direct SQL insert with `is_global=1` and `organizer_id=1` fails CHECK constraint. Test Organizer deletion nullifies FK. |
| Unit | Backfill Determinism | Test the subject-mapping logic inside the backfill command returns correct `organizer_id` or `null` for unknown/soft-deleted subjects. |
| Integration | Query Isolation | Assert `ActivityLogQueryService` returns 0 rows for other tenants or global rows when accessed by a tenant admin. Verify `super_admin` team `0` can see all. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. This change isolates data within the database schema and application scopes.

## Migration / Rollout

1. Deploy migration (adds columns and CHECK constraint, non-blocking).
2. Deploy code (new code begins writing tenant ID / global flags immediately).
3. Post-deploy: Execute `php artisan activitylog:backfill-organizer --chunk=1000` to classify historical rows safely.
4. Unclassifiable rows remain invisible to tenants, failing safely.

## Open Questions

- None. The scope is now strictly defined, mitigating previous seam and policy risks. The implementation size estimate (schema + seam + tests + backfill) is approx 700 lines, staying under the 800-line budget for this `foundation` phase.
