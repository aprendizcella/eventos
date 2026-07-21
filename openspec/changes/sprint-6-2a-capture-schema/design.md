# Design: sprint-6-2a-capture-schema

## Technical Approach

We will add ownership (`organizer_id`) and visibility (`is_global`) classifications to the existing `activity_log` table. Since this table is pre-populated by the Spatie ActivityLog package and legacy events, these fields must support nullable/legacy states while strictly preventing invalid ownership logic natively in the database schema. This migration handles data preservation, foreign keys, query indexes, and engine-specific database invariants without touching application-level capture or query logic.

## Architecture Decisions

### Decision: Invariant Enforcement across Database Engines
**Choice**: Use `ALTER TABLE ... ADD CONSTRAINT CHECK` for MariaDB and `BEFORE INSERT`/`BEFORE UPDATE` triggers for SQLite.
**Alternatives considered**: Using application-level validation only (rejected: does not enforce physical invariants); dropping and rebuilding the `activity_log` table in SQLite to add table-level check constraints (rejected: too invasive and risky for migrations testing).
**Rationale**: MariaDB 11 supports raw `ALTER TABLE ... ADD CONSTRAINT` natively. SQLite's `ALTER TABLE` does not support adding constraints to an existing table. Using SQLite `RAISE(ABORT)` triggers explicitly fulfills the requirement for a "SQLite-compatible schema operation that enforces the same invalid-combination rejection" without false greens in PHPUnit.

### Decision: Foreign Key Policy
**Choice**: Explicit `restrictOnDelete()` for `organizer_id` linking to `organizers.id`.
**Alternatives considered**: `nullOnDelete()` (rejected: destroys immutable audit history ownership), `cascadeOnDelete()` (rejected: physical deletion should never arbitrarily destroy audit logs).
**Rationale**: Preserves immutable activity ownership. Soft deletes on `organizers` (which is their default behavior) will not nullify the ID, and any attempted physical force-deletion of an organizer will correctly fail if audit trails remain.

### Decision: Legacy Row Handling
**Choice**: Set `is_global` database default to `false` and allow `organizer_id` to be nullable.
**Alternatives considered**: Backfilling legacy rows to global (rejected: legacy events are not implicitly global platform events).
**Rationale**: Existing populated data remains intact. An event with `is_global = false` and `organizer_id IS NULL` is an explicitly unclassified legacy state and remains distinct from true global events, perfectly matching the required invariant.

### Decision: Indexing Strategy
**Choice**: Composite indexes `['organizer_id', 'created_at']` and `['is_global', 'created_at']`.
**Alternatives considered**: Single column indexes on `organizer_id` and `is_global`.
**Rationale**: Real-world query paths for activity logs will filter by ownership or visibility and then order by timestamp for chronological pagination. Compounding `created_at` provides deterministic ordering immediately out of the index.

## Data Flow

    [Migration Runner]
         │
         ├── Schema::table('activity_log')
         │   ├── Add organizer_id (nullable)
         │   ├── Add is_global (default false)
         │   ├── Add FK -> organizers.id (RESTRICT)
         │   └── Add Composite Indexes
         │
         └── DB::statement (Engine Routing)
             ├── MariaDB: ADD CONSTRAINT CHECK
             └── SQLite: CREATE TRIGGER (Insert/Update)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/2026_07_20_xxxxxx_add_ownership_to_activity_log_table.php` | Create | Adds columns, keys, indexes, and engine-specific invariants (MariaDB CHECK constraint / SQLite triggers). Rollback properly drops them. |
| `tests/Feature/Audit/SchemaTest.php` | Create | Tests migration sequence, invariant enforcement (throws DB exceptions on invalid data), successful legacy row states, and reversible safe rollback warning. |

## Interfaces / Contracts

**Database Invariant**:
`is_global = 1` implies `organizer_id IS NULL`
If `is_global = 1 AND organizer_id IS NOT NULL` -> `RAISE(ABORT)` (SQLite) or `CONSTRAINT violation` (MariaDB).

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (Schema) | Valid classification | Test `is_global=false` with organizer, `is_global=true` without organizer, `is_global=false` without organizer. |
| Unit (Schema) | Invariant Rejection | Try inserting/updating a row with `is_global=true` and a valid `organizer_id`. Verify `Illuminate\Database\QueryException` is thrown. |
| Unit (Schema) | Foreign Key Rules | Ensure `restrictOnDelete` blocks physical deletion of an organizer with activities, but soft deletion works. |
| Unit (Schema) | Migration Integrity | Run `migrate`, check schema for columns and indexes, run `migrate:rollback`, verify removal order and warn about data loss in test output. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No data migration required. Existing `activity_log` rows automatically become `is_global = 0` with `organizer_id = NULL` due to table defaults, safely preserving their legacy unclassified state without risking physical data loss. A rollback destroys any explicitly captured ownership classification, which is expected and documented.

## Open Questions

- None. Ready for tasks.