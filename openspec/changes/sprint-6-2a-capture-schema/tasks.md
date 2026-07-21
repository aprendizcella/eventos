# Tasks: sprint-6-2a-capture-schema

## Review Workload Forecast

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

| Field | Value |
|-------|-------|
| Estimated changed lines | ~200 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Not needed |
| Delivery strategy | single-pr-default |
| Chain strategy | pending |

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Schema additions and validation | PR 1 | `vendor/bin/sail artisan test --filter SchemaTest` | N/A (Schema only) | `php artisan migrate:rollback` (drops columns & constraints) |

## Phase 1: Foundation / Infrastructure (Migration)

- [x] 1.1 Create migration: `vendor/bin/sail artisan make:migration add_ownership_to_activity_log_table --table=activity_log`.
- [x] 1.2 Implement `up()` schema changes: Add `organizer_id` (nullable bigint unsigned) with FK `restrictOnDelete()`, `is_global` (boolean default false), and composite indexes.
- [x] 1.3 Implement `up()` DB invariants: Check DB driver, add `ALTER TABLE ... ADD CONSTRAINT CHECK` for MariaDB/MySQL, and `CREATE TRIGGER` for SQLite `BEFORE INSERT` and `BEFORE UPDATE`.
- [x] 1.4 Implement `down()`: Safely drop triggers/constraints, indexes, FK, and columns in reverse dependency order.

## Phase 2: Testing / Verification

- [x] 2.1 Create test: `vendor/bin/sail artisan make:test --pest Audit/SchemaTest`.
- [x] 2.2 Write test: Verify valid classifications are accepted (legacy, global, owned).
- [x] 2.3 Write test: Verify invariant rejection (`is_global=true` with `organizer_id` throws `QueryException`).
- [x] 2.4 Write test: Verify `restrictOnDelete` blocks physical deletion of an organizer but allows soft deletion.
- [x] 2.5 Write test: Verify migration integrity, rollback success, and outputs a data-loss warning.
