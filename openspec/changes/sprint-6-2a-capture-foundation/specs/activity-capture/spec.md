# Activity Capture Foundation Specification

## Confirmed Evidence

Confirmed: single-database `Organizer` (`organizers.id`), domain/route resolution, Permission `organizer_id`, no `tenant_id`; Laravel 12.62/PHP 8.4, Activitylog 5.0.0, MariaDB 11, SQLite/Pest 4.7. Buffering uses query-builder `insert`, bypassing model events.

## Design Assumptions

Design will select the physical-delete/FK strategy, metadata API, and SQLite constraint implementation.

## Requirements

### Requirement: Authoritative activity ownership schema

The system MUST add nullable `organizer_id` referencing `organizers.id` and non-null `is_global` defaulting false. `is_global=true` MUST imply null organizer; false MAY be unclassified. `tenant_id` MUST NOT be added.

#### Scenario: Valid classifications
- GIVEN a capture has organizer 7 or an explicit global marker
- WHEN persisted
- THEN it contains respectively `7,false` or `NULL,true`

#### Scenario: Constraint rejects contradiction
- GIVEN a write attempts global=true with an organizer
- WHEN MariaDB evaluates it
- THEN the database rejects it and a test proves rejection

### Requirement: Immutable ownership and organizer deletion

Ownership MUST be assigned before persistence and immutable thereafter. Soft deletion MUST preserve `organizer_id`. Physical deletion MUST refuse or use a documented identity-preserving mechanism; it MUST NOT silently null/reclassify ownership. `nullOnDelete` is prohibited when it loses attribution.

#### Scenario: Deletion preserves attribution
- GIVEN an activity belongs to organizer 7
- WHEN that organizer is deleted
- THEN attribution remains 7 or deletion is refused

### Requirement: Explicit classification contract

The seam MUST accept explicit organizer/global metadata. Explicit global (`true`) requires null organizer and overrides context. Explicit organizer MUST match current Organizer when present. Conflicts MUST fail. Without explicit metadata, current Organizer supplies ownership; without either, the row is `NULL,false`, never global.

#### Scenario: Conflicting metadata
- GIVEN current organizer 7 plus explicit organizer 8, or global plus organizer 8
- WHEN ActivityLogger runs
- THEN it fails before persistence and creates no row

#### Scenario: Missing context
- GIVEN no marker and no current Organizer
- WHEN ActivityLogger runs
- THEN it persists as `NULL,false`

### Requirement: All ActivityLogger paths use the classification seam

Custom `LogActivityAction` MUST classify before immediate or buffered insertion. Tenant-aware jobs MUST restore Organizer context; `NotTenantAware` jobs are context-free unless explicitly marked. Console operations MUST explicitly declare ownership. Direct/raw writes are prohibited and covered by tests; the guarantee applies only to ActivityLogger.

#### Scenario: Buffered tenant capture
- GIVEN buffering is enabled or a tenant-aware job runs for organizer 7
- WHEN it logs and the buffer flushes
- THEN the row remains `7,false`

#### Scenario: Global and context-free capture
- GIVEN console declares global, or a non-tenant-aware job has no marker
- WHEN it logs
- THEN results are respectively `NULL,true` and `NULL,false`

### Requirement: Portable migration and verification

The migration MUST run on MariaDB 11 and SQLite PHPUnit without weakening semantics. MariaDB MUST verify foreign-key/CHECK behavior. SQLite MUST enable foreign keys and test the same contract using a compatible strategy. Rollback MUST warn that dropping ownership columns is destructive and require explicit preservation/consumer review.

#### Scenario: Portable migration and rollback
- GIVEN fresh SQLite and MariaDB 11 databases
- WHEN migration, schema, invariant, and rollback checks run
- THEN both pass and evidence shows no silent data loss

### Requirement: Capture regression coverage

Pest feature tests MUST cover tenant/global/missing-context/conflict captures, buffered writes, both queue types, console/global capture, deletion, constraints, and cleanup. Tests MUST prove organizer 7 cannot leak into later global or organizer 8 captures.

#### Scenario: No context leakage
- GIVEN capture one runs under organizer 7
- WHEN context is cleared before global and organizer 8 captures
- THEN each row has only its declared ownership

## Non-Goals

Excludes visibility SQL, historical backfill, Livewire/Volt UI, GDPR, MFA, retention, and raw-write support.

## Acceptance Criteria

- Schema/constraints are verified on MariaDB 11 and SQLite.
- ActivityLogger paths are classified before persistence, including buffering.
- Ownership is immutable; deletion cannot erase attribution.
- Conflict, queue, console, missing-context, and leakage tests pass.
- Estimated review: 650–780 authored lines including migration, seam, tests; PR within 800.
