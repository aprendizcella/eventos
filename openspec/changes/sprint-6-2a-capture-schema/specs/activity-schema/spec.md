# Audit Capture Schema Specification

## Purpose

Define physical ownership and classification metadata for `activity_log` in the shared Organizer database. This specification covers schema behavior only; it does not define how application code captures, classifies, queries, or displays activities.

## Confirmed Evidence

- `activity_log` currently has no ownership columns and is populated by the existing Spatie ActivityLog migration.
- `organizers.id` is an unsigned bigint primary key; `organizers` uses `SoftDeletes`.
- Runtime compose uses MariaDB 11; PHPUnit uses SQLite `:memory:` with foreign-key constraints enabled by default.
- Project migrations use timestamped, reversible migrations, explicit foreign keys, indexes, and `deleted_at` on Organizer records.

## Requirements

### Requirement: Ownership and classification columns

The system MUST add nullable unsigned-bigint `activity_log.organizer_id` referencing `organizers.id`, and MUST add non-null boolean `activity_log.is_global` with a database default of `false`.

#### Scenario: Migrate a populated activity log

- GIVEN `activity_log` contains rows and existing columns/data
- WHEN the schema migration runs
- THEN every existing row and existing value remains intact
- AND `is_global` is non-null and defaults to `false`
- AND existing rows with no organizer remain representable.

#### Scenario: Valid ownership classifications

- GIVEN an activity row is inserted or updated
- WHEN it has `is_global=false` with an organizer, `is_global=true` without an organizer, or `is_global=false` without an organizer
- THEN the database accepts the row.

### Requirement: Global invariant and legacy classification

The database MUST enforce `is_global=true` implies `organizer_id IS NULL`. `organizer_id IS NULL AND is_global=false` MUST remain a valid, explicitly unclassified legacy state; this change MUST NOT backfill or reinterpret it.

#### Scenario: Reject global activity with ownership

- GIVEN an activity row has a valid organizer ID
- WHEN a write sets `is_global=true`
- THEN the database rejects the write and preserves the invariant.

#### Scenario: Preserve unclassified legacy rows

- GIVEN a pre-existing row has no organizer ID
- WHEN the migration completes
- THEN it is stored as `is_global=false` and remains distinguishable from a global row.

### Requirement: Referential integrity and ownership preservation

The foreign key MUST use restrictive physical-delete behavior (`restrictOnDelete()` semantics) and MUST NOT nullify or cascade activity ownership. Organizer soft deletion MUST NOT alter `activity_log.organizer_id`; physical deletion MUST fail while owned activity rows exist.

#### Scenario: Soft delete an organizer

- GIVEN an activity references an organizer
- WHEN that organizer is soft-deleted
- THEN the activity remains and retains its organizer ID.

#### Scenario: Physically delete an organizer

- GIVEN an activity references an organizer
- WHEN the organizer is force-deleted
- THEN the database rejects the deletion and retains the activity ownership.

### Requirement: Query-supporting indexes

The schema MUST provide named composite indexes `activity_log_organizer_id_created_at_index` and `activity_log_is_global_created_at_index`, supporting ownership/global filtering followed by chronological pagination.

#### Scenario: Inspect query-supporting indexes

- GIVEN the migration has run
- WHEN schema metadata is inspected
- THEN both named indexes exist with columns in the stated order.

### Requirement: Engine-compatible constraint path

The migration MUST apply successfully against MariaDB 11 and PHPUnit SQLite without silently weakening the invariant. MariaDB MUST enforce the check constraint natively; the SQLite path MUST use a SQLite-compatible schema operation that enforces the same invalid-combination rejection.

#### Scenario: Verify both engines

- GIVEN the schema test runs against each configured engine
- WHEN valid and invalid classification writes are attempted
- THEN both engines accept the three valid combinations and reject `is_global=true` with an organizer.

### Requirement: Safe ordering and rollback warning

The migration MUST require `organizers` to exist before creating the foreign key, add dependent objects before removing them on rollback, and provide a reversible `down` path. Rollback documentation/tests MUST warn that dropping the new columns destroys captured ownership/classification data.

#### Scenario: Verify migration order and rollback

- GIVEN the application has migrated up and schema tests have inserted new metadata
- WHEN rollback is explicitly executed
- THEN indexes, constraint, foreign key, and columns are removed in dependency-safe order
- AND the test records the ownership/classification data-loss warning.

### Requirement: No capture-seam assumptions

This schema change MUST NOT require, invoke, or assume an ActivityLogger capture seam, visibility service, backfill, UI, GDPR behavior, or MFA behavior.

#### Scenario: Schema-only execution

- GIVEN the migration is run with existing activity rows
- WHEN no new application capture code is installed
- THEN migration and schema verification complete solely from database state and remain independent of capture classification.

## Design Decisions Remaining

`sdd-design` MUST select the concrete SQLite constraint/rebuild strategy and the exact migration/test transaction boundaries, then prove the approach on both engines.

**Estimated specification size:** approximately 610 words / 111 lines; implementation review forecast remains below the 800-line budget.
