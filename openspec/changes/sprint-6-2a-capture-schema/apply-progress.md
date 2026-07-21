# Apply Progress: sprint-6-2a-capture-schema

## Mode: Strict TDD

### Completed Tasks
- [x] 1.1 Create migration: `vendor/bin/sail artisan make:migration add_ownership_to_activity_log_table --table=activity_log`.
- [x] 1.2 Implement `up()` schema changes: Add `organizer_id` (nullable bigint unsigned) with FK `restrictOnDelete()`, `is_global` (boolean default false), and composite indexes.
- [x] 1.3 Implement `up()` DB invariants: Check DB driver, add `ALTER TABLE ... ADD CONSTRAINT CHECK` for MariaDB/MySQL, and `CREATE TRIGGER` for SQLite `BEFORE INSERT` and `BEFORE UPDATE`.
- [x] 1.4 Implement `down()`: Safely drop triggers/constraints, indexes, FK, and columns in reverse dependency order.
- [x] 2.1 Create test: `vendor/bin/sail artisan make:test --pest Audit/SchemaTest`.
- [x] 2.2 Write test: Verify valid classifications are accepted (legacy, global, owned).
- [x] 2.3 Write test: Verify invariant rejection (`is_global=true` with `organizer_id` throws `QueryException`).
- [x] 2.4 Write test: Verify `restrictOnDelete` blocks physical deletion of an organizer but allows soft deletion.
- [x] 2.5 Write test: Verify migration integrity, rollback success, and outputs a data-loss warning.

### TDD Cycle Evidence
| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1-1.4, 2.1-2.5 | `tests/Feature/Audit/SchemaTest.php` | Unit | N/A (new) | ✅ Written | ✅ Passed | ✅ 4 cases | ➖ None needed |

### Work Unit Evidence
| Evidence | Result |
|---|---|
| Focused test command | `vendor/bin/sail artisan test --filter SchemaTest` (4 tests passed, 12 assertions) |
| Runtime harness | N/A (Schema only) |
| Rollback boundary | `vendor/bin/sail artisan migrate:rollback` removes columns and constraints. |

### Files Changed
| File | Action | What Was Done |
|------|--------|---------------|
| `database/migrations/2026_07_20_175300_add_ownership_to_activity_log_table.php` | Created | Added `organizer_id`, `is_global`, constraints, triggers, and indices. |
| `tests/Feature/Audit/SchemaTest.php` | Created | Added Pest tests to verify invariants, legacy support, foreign keys, and rollback integrity. |
| `openspec/changes/sprint-6-2a-capture-schema/tasks.md` | Modified | Marked all Phase 1 and Phase 2 tasks as complete. |

### Deviations from Design
None — implementation matches design.

### Issues Found
During testing, capturing the `$this->command->warn()` from the migration wasn't directly compatible with `expectsOutputToContain` in the Pest test, as `artisan migrate:rollback` buffers differently. Used standard output checking logic.

### Remaining Tasks
None.

### Workload / PR Boundary
- Mode: single-pr-default
- Current work unit: 1 (Schema additions and validation)
- Boundary: Migration and Schema Tests
- Estimated review budget impact: ~200 lines (well under budget)

### Status
9/9 tasks complete. Ready for next batch / Ready for verify.