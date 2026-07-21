# Proposal: sprint-6-2a-capture-schema

## Intent

The `activity_log` table currently lacks authoritative tenant isolation. To enable tenant-isolated audit logs and strictly segregated global audits, we must establish physical ownership at the schema level. This specific unit adds the physical database schema invariants (`organizer_id`, `is_global`) and indexes necessary to capture ownership. This schema change is foundational and precedes the application-level capture seam, ensuring that physical data invariants (e.g., global rows cannot have an organizer) are enforced at the database level and tested thoroughly.

## Scope

### In Scope
- Add `organizer_id` (`bigint unsigned, nullable`) to the `activity_log` table, linked via foreign key to `organizers.id`.
- Add `is_global` (`boolean, default false`) to explicitly mark global activities.
- Enforce invariant at the schema/migration level: global rows cannot carry an organizer ID (`is_global = true` implies `organizer_id IS NULL`).
- Preserve immutable audit ownership across `organizers` soft deletes or physical deletes; use `restrictOnDelete()` to prevent nullifying historical ownership.
- Add indexes optimized for future queries (e.g., `organizer_id` and `is_global` compounded with `created_at`).
- Ensure compatibility between the MariaDB 11 runtime and SQLite PHPUnit testing environments.
- Add schema, invariant, and rollback evidence tests.

### Out of Scope
- Implementing the ActivityLogger capture seam (deferred to `sprint-6-2a-capture-seam`).
- Building the Visibility query service and authorization boundaries (deferred to visibility unit).
- Historical backfill of existing rows (deferred to backfill unit).
- Livewire/Volt UI, GDPR compliance, or MFA integrations.

## Capabilities

### New Capabilities
- `audit-capture-schema`: Physical schema invariants and classification columns defining ownership and global status.

### Modified Capabilities
- None

## Approach

1. **Migration Creation**: Create a Laravel migration modifying `activity_log` to add `organizer_id` and `is_global`.
2. **Constraint Definition**: Add a table constraint (`CHECK (is_global = 0 OR organizer_id IS NULL)`) to guarantee the invariant. Since SQLite has limited `ALTER TABLE` support for constraints, we'll ensure SQLite testing compatibility by applying raw check constraints defensively (e.g. checking driver) or applying it at table creation for SQLite. 
3. **Foreign Key Policy**: Define the foreign key on `organizer_id` referencing `organizers.id` using `restrictOnDelete()`. Do NOT use `nullOnDelete()`. This preserves immutable ownership.
4. **Indexes**: Add composite index `activity_log_organizer_id_created_at_index` and `activity_log_is_global_created_at_index` for fast pagination.
5. **Testing**: Write a dedicated `AuditSchemaTest` to verify that migrations up/down correctly, constraints restrict invalid rows (where supported), and the new columns are available.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | Migration adding `organizer_id`, `is_global`, constraints, and indexes. |
| `tests/Feature/Audit/SchemaTest.php` | New | Verification of schema invariant, nullable constraints, and rollback. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| SQLite vs MariaDB Check Constraints | High | Laravel migrations often fail when applying raw `CHECK` constraints to existing tables in SQLite. Mitigation: Wrap constraint creation in DB driver checks (skip in SQLite or test natively using MariaDB via separate DB connection if needed). |
| Data Loss on Delete | Medium | Use `restrictOnDelete()` to prevent physical removal of logs. |
| Review Budget | Low | Strict bounding to schema-only changes keeps the unit well under the 800-line budget. |

## Rollback Plan

- Run `php artisan migrate:rollback`. The `down` method must safely drop the added check constraints, foreign keys, indexes, and columns (`organizer_id`, `is_global`) in the correct order.

## Dependencies

- Spatie ActivityLog existing table.
- `organizers` table (requires it to exist before this migration).

## Success Criteria

- [ ] Migration adds `organizer_id` and `is_global` to `activity_log`.
- [ ] Foreign key uses restrictOnDelete to preserve ownership.
- [ ] MariaDB check constraint enforces `is_global = true` -> `organizer_id IS NULL`.
- [ ] Schema applies successfully in both MariaDB and SQLite (PHPUnit).
- [ ] Automated tests verify constraints, index creation, and rollback safety.
