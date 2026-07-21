# Tasks: Sprint 6.2a Capture Foundation

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated authored lines | 120–180 |
| 800-line budget risk | Low |
| Delivery strategy | single-pr-default |
| Chain strategy | not applicable |

## Phase 1: RED Tests and Schema

- [x] 1.1 Add RED Pest tests in `tests/Feature/Audit/ActivityCaptureTest.php` for columns, `organizers.id` FK, restrict deletion, `is_global` invariant, SQLite FK enablement, and destructive rollback warning.
- [x] 1.2 Create `database/migrations/*_add_classification_to_activity_log.php` (assumed timestamp) with nullable `organizer_id`, default-false `is_global`, restrict FK, MariaDB CHECK, SQLite-compatible rebuild, reversible `down()`.

## Phase 2: Classification Seam

- [x] 2.1 Add RED tests for the truth table: explicit global/organizer, current Organizer, missing context, conflicts, metadata stripping, and no persistence on failure.
- [x] 2.2 Create `app/Actions/Audit/LogActivityAction.php` extending Spatie’s action; resolve `Organizer::current()`, validate precedence/conflicts, assign columns before immediate/buffered insert, and strip reserved metadata.
- [x] 2.3 Modify `config/activitylog.php` to register the custom action; add a convention test documenting ActivityLogger-only support and rejecting direct/raw write assumptions.

## Phase 3: Context Integration

- [x] 3.1 Add RED tests for buffered tenant capture and context leakage across Organizer 7, global, and Organizer 8.
- [x] 3.2 Add RED tests for `TenantAware` and `NotTenantAware` jobs, explicit global console capture, and context-free unclassified capture; use existing `Organizer`/multitenancy patterns.
- [x] 3.3 Make the seam pass all context tests, including cleanup/reset after each capture and explicit global API/metadata contract.

## Phase 4: Verification

- [x] 4.1 Verify SQLite and MariaDB 11 schema/invariant behavior, soft-delete attribution, physical-delete refusal, and cleanup with focused Pest tests.
- [x] 4.2 Run `vendor/bin/sail composer run test`, Pint, PHPStan, and record focused/runtime/rollback evidence; exclude visibility, backfill, UI, GDPR, and MFA.
