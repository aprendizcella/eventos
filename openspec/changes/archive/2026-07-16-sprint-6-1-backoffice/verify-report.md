```yaml
schema: gentle-ai.verify-result/v1
evidence_revision: sha256:2026-07-16-verify-sprint-6-1-backoffice-v3
verdict: pass
blockers: 0
critical_findings: 0
requirements: 41/41
scenarios: 72/72
test_command: vendor/bin/sail artisan test --compact
test_exit_code: 0
test_output_hash: sha256:69554ae19524d97a40165c2b6e5ff99b8eb704feeb454c1d373eeea2e03d20f7
build_command: vendor/bin/sail php vendor/bin/phpstan analyse --memory-limit=512M
build_exit_code: 0
build_output_hash: sha256:30f8c06a0c1242a6a93decd370768236cf4e36864faf0a97ff1db3726010d486
```

## Verification Report

**Change**: sprint-6-1-backoffice
**Version**: v3 (fresh verification after native review blocker fixes)
**Mode**: Standard (Strict TDD not active)

### Completeness

| Metric | Value |
|--------|-------|
| Tasks total | 18 |
| Tasks complete | 18 |
| Tasks incomplete | 0 |
| Phases | 5/5 complete |
| Specs | 9 capability specs |
| Requirements | 41 |
| Scenarios | 72 |

### Build & Tests Execution

**Build (PHPStan)**: ✅ Passed
```
vendor/bin/sail php vendor/bin/phpstan analyse --memory-limit=512M
252/252 files analyzed — [OK] No errors
```

**Tests**: ✅ 928 passed / ❌ 0 failed / ⚠️ 0 skipped
```
vendor/bin/sail artisan test --compact
Tests: 928 passed (2395 assertions) — 33.08s
```

**Pint**: ✅ Passed (`vendor/bin/sail bin pint --test --dirty`)
**Rector**: ✅ Passed (`vendor/bin/sail composer run rector -- --dry-run`)

### Spec Compliance Matrix

#### Capability: admin-authorization (3 reqs, 6 scenarios)

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| Global Admin Context | Global admin request uses team 0 | `AdminAuthorizationTest` | ✅ COMPLIANT |
| Global Admin Context | Tenant context ignored for admin access | `AdminAuthorizationTest` | ✅ COMPLIANT |
| Admin Role Matrix | Super admin can grant global role | `UserManagementTest` | ✅ COMPLIANT |
| Admin Role Matrix | Platform admin cannot grant global role | `UserManagementTest` | ✅ COMPLIANT |
| Sanctum API Authorization | Authenticated admin can call API | `AdminApiTest` | ✅ COMPLIANT |
| Sanctum API Authorization | Unauthenticated rejected (401) | `AdminApiTest` | ✅ COMPLIANT |

**Compliance**: 6/6 ✅

---

#### Capability: admin-user-management (5 reqs, 7 scenarios)

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| User Administration | Admin lists users | `AdminApiTest::paginates users list` | ✅ COMPLIANT |
| User Administration | View user details (show endpoint) | `AdminApiTest::fetches a specific user` → 200 ✅ | ✅ COMPLIANT |
| Suspension and Restore | Suspend user invalidates access | `UserManagementTest` | ✅ COMPLIANT |
| Suspension and Restore | Restore user re-enables access | `UserManagementTest` | ✅ COMPLIANT |
| Password Reset Link | Admin sends reset link | `UserManagementTest` | ✅ COMPLIANT |
| Final Super Admin Protection | Last super admin protected | `UserManagementTest` | ✅ COMPLIANT |
| GDPR Deletion Deferred | Deletion request out of scope | `AdminApiTest::defers GDPR deletion` (asserts 405) | ✅ COMPLIANT |

**Show endpoint coverage (dedicated audit)**:
| Status | Test | Result |
|--------|------|--------|
| 200 | `AdminApiTest::fetches a specific user()` line 65 | ✅ |
| 401 | `AdminApiTest::requires authentication to fetch a specific user()` line 81 | ✅ |
| 403 | `AdminApiTest::prevents non-admins from fetching a specific user()` line 86 | ✅ |
| 404 | `AdminApiTest::returns 404 when fetching a non-existent user()` line 75 | ✅ |

**Compliance**: 7/7 ✅

---

#### Capability: admin-event-moderation (4 reqs, 6 scenarios)

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| Reversible Suspension | Suspend published event | `EventModerationTest` | ✅ COMPLIANT |
| Reversible Suspension | Restore suspended event | `EventModerationTest` | ✅ COMPLIANT |
| Suspension Audit | Suspension requires reason | `EventModerationTest` (ValidationException) | ✅ COMPLIANT |
| Suspension Audit | Suspension records actor | `EventModerationTest` (causer_id + properties) | ✅ COMPLIANT |
| Catalog/Search Exclusion | Suspended event hidden | `SuspendedEventExclusionTest` | ✅ COMPLIANT |
| No Financial Side Effects | Suspension does not refund | `EventModerationTest::it does not trigger automatic refunds or payout changes on suspension` | ✅ COMPLIANT |

**No-refund proof (dedicated audit)**: `SuspendEventAction` and `RestoreEventAction` contain zero references to refund, payout, or payment logic. Test at line 59-77 of EventModerationTest fakes Queue, Http, and Event; asserts no side effects triggered. ✅

**Compliance**: 6/6 ✅

---

#### Capability: platform-settings (4 reqs, 6 scenarios)

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| Singleton Settings Record | Settings record exists once | `PlatformSettingTest` | ✅ COMPLIANT |
| Concurrency and Validation | Invalid settings rejected | `FormRequestsTest` | ✅ COMPLIANT |
| Concurrency and Validation | Concurrent update rejected | `PlatformSettingsTest` | ✅ COMPLIANT |
| Commission Fallback Precedence | Organizer setting wins | `CommissionFallbackTest` | ✅ COMPLIANT |
| Commission Fallback Precedence | Explicit zero honored | `CommissionFallbackTest` | ✅ COMPLIANT |
| Future Payouts Only | Historical payout unchanged | `CommissionFallbackTest::historical payouts remain immutable` | ✅ COMPLIANT |

**Compliance**: 6/6 ✅

---

#### Capability: event-lifecycle (MODIFIED — 5 reqs, 10 scenarios)

All 10 scenarios compliant. `EventStatus` enum includes `Suspended` case (7 cases total, verified by `EventStatusTest`). `SuspendEventAction` stores `previous_status`; `RestoreEventAction` restores via `EventStatus::from()`.

**Compliance**: 10/10 ✅

---

#### Capability: event-authorization (MODIFIED — 4 reqs, 10 scenarios)

All 10 scenarios compliant. Global admin access verified through team-0 middleware and `AdminApiTest`. `platform_admin` can suspend events but NOT users (role matrix enforced in controller).

**Compliance**: 10/10 ✅

---

#### Capability: commission-tracking (MODIFIED — 5 reqs, 6 scenarios)

All 6 scenarios compliant. Commission fallback chain: organizer → platform → hardcoded. Explicit zero preserved via `??=` (only applies when key absent/null). Historical payouts immutable.

**Compliance**: 6/6 ✅

---

#### Capability: public-catalog (MODIFIED — 6 reqs, 11 scenarios)

All 11 scenarios compliant. Suspended events excluded from public catalog via `published()` and `public()` scopes.

**Compliance**: 11/11 ✅

---

#### Capability: event-search (MODIFIED — 5 reqs, 10 scenarios)

All 10 scenarios compliant. `shouldBeSearchable()` returns `false` for `Suspended` status. Scout indexing respects the contract.

**Compliance**: 10/10 ✅

---

### Compliance Summary

| Capability | Scenarios | Compliant | Untested/Failing |
|------------|-----------|-----------|------------------|
| admin-authorization | 6 | 6 | 0 |
| admin-user-management | 7 | 7 | 0 |
| admin-event-moderation | 6 | 6 | 0 |
| platform-settings | 6 | 6 | 0 |
| event-lifecycle | 10 | 10 | 0 |
| event-authorization | 10 | 10 | 0 |
| commission-tracking | 6 | 6 | 0 |
| public-catalog | 11 | 11 | 0 |
| event-search | 10 | 10 | 0 |
| **Total** | **72** | **72** | **0** |

**Overall compliance**: 72/72 scenarios compliant (100%)

### Correctness (Static Evidence)

| Implementation | Status | Evidence |
|----------------|--------|----------|
| `EnsureGlobalAdminContext` middleware | ✅ | Sets team 0 in `handle()`, restores in `terminate()`; registered as `global.admin` in `bootstrap/app.php` |
| `EventStatus::Suspended` enum case | ✅ | 7 cases total; `EventStatusTest` verifies |
| `User::$suspended_at`, `isSuspended()`, `active()` scope | ✅ | Cast to `datetime`; `active()` scope = `whereNull('suspended_at')` |
| `Event::$previous_status`, `$suspended_at` | ✅ | Stored on suspend, cleared on restore |
| `PlatformSetting` singleton | ✅ | `current()` uses `firstOrCreate(['is_singleton' => true])`; JSON `settings` + `lock_version` |
| `SuspendEventAction` | ✅ | Stores `previous_status`, sets `Suspended`, requires `reason`, logs actor+reason; 0 refund/payout code |
| `RestoreEventAction` | ✅ | Restores via `EventStatus::from()`, clears suspension fields; 0 refund/payout code |
| `SuspendUserAction` | ✅ | `DB::transaction` + `lockForUpdate()` for concurrency-safe last `super_admin` guard; deletes all tokens via `$user->tokens()->delete()`; deletes sessions via `DB::table('sessions')` |
| `RestoreUserAction` | ✅ | Clears `suspended_at` |
| `SendPasswordResetAction` | ✅ | Uses `Password::broker()->sendResetLink()` |
| `AssignGlobalRoleAction` | ✅ | Guards `platform_admin` (403), creates role with `organizer_id=0` |
| `RevokeGlobalRoleAction` | ✅ | Guards `platform_admin` (403) |
| `UpdatePlatformSettingsAction` | ✅ | Double CAS: app-layer `lock_version` check + DB-level `where('lock_version', $lockVersion)`; increments `lock_version`; activity-logs with `causedBy($actor)` |
| `CreatePayoutAction::resolveBillingSettings()` | ✅ | Chain: organizer → platform → `config()`; `??=` preserves explicit zero; snapshot at creation |
| `UserApiController` | ✅ | `index` (paginated), `show` (single user w/ 401/403/404/200), `suspend`, `restore`; NO delete endpoint |
| `EventApiController` | ✅ | `index` (withoutGlobalScopes), `suspend` (uses `SuspendEventRequest` validating `reason`), `restore` |
| `UserResource` | ✅ | Includes `is_suspended`, `suspended_at`, `global_roles` |
| `PlatformSettingResource` | ✅ | Includes `settings`, `lock_version`, `updated_at` |
| Admin routes (`/admin/*`) | ✅ | Volt components under `role:super_admin|platform_admin` middleware |
| API routes (`/api/v1/admin/*`) | ✅ | Sanctum + `global.admin` + `role:super_admin|platform_admin` + `throttle:60,1`; no DELETE route exposed |
| `shouldBeSearchable()` | ✅ | Requires `Published` + `Public`; `Suspended` excluded |
| `EventSearchService` | ✅ | `published()` + `public()` scopes exclude suspended |
| Admin layout | ✅ | `layouts/admin.blade.php` extends `app-shell` with `admin-sidebar` navigation |
| Volt components | ✅ | `dashboard` (admin welcome), `users` (pagination + suspend/restore w/ actions), `events` (pagination + suspend/restore w/ reason), `settings` (form + `lockVersion` tracking + save action) |
| `config/permission.php` | ✅ | Team model = `Organizer`; global roles use team 0 |

### Dedicated Audit: User-Requested Verification Points

#### 1. Suspended Users — Token/Session Invalidation ✅

**Code**: `SuspendUserAction` (line 30-33):
```php
$user->suspended_at = now();
$user->save();
$user->tokens()->delete();
DB::table('sessions')->where('user_id', $user->id)->delete();
```
On suspend: sets `suspended_at` → deletes all Sanctum tokens → deletes all sessions. Suspended users can no longer authenticate with existing tokens or sessions.

#### 2. Last Active Super Admin Protection — Concurrency-Safe ✅

**Code**: `SuspendUserAction` (line 16-28):
```php
DB::transaction(function () use ($user) {
    if ($user->hasRole('super_admin')) {
        $activeSuperAdmins = User::query()
            ->role('super_admin')
            ->whereNull('suspended_at')
            ->lockForUpdate()  // Concurrency-safe: row-level lock
            ->count();
        if ($activeSuperAdmins <= 1 && !$user->isSuspended()) {
            throw ValidationException::withMessages([...]);
        }
    }
    ...
});
```
Uses `DB::transaction` + `lockForUpdate()` for atomicity. Counts only non-suspended `super_admin` users. Rejects if exactly 1 remains.

#### 3. PlatformSetting Singleton + Optimistic Locking ✅

**Singleton**: `PlatformSetting::current()` — `firstOrCreate(['is_singleton' => true], ['settings' => [], 'lock_version' => 0])`. One record, always.

**Atomic optimistic locking**: `UpdatePlatformSettingsAction` (line 19-41):
- App-layer check: `$platformSetting->lock_version !== $lockVersion` → reject
- DB-level CAS: `->where('id', $id)->where('lock_version', $lockVersion)->update([... 'lock_version' => $platformSetting->lock_version + 1])`
- Fallback check: if `$updated === 0` → reject (another update won the race)
- Both layers tested: `PlatformSettingsTest` covers concurrent rejection

#### 4. Admin Volt Dashboard/Users/Events/Settings — Functional ✅

| Component | Lines | Functionality |
|-----------|-------|---------------|
| `dashboard` | 21 | Admin welcome page with layout, heading, description |
| `users` | 57 | `WithPagination`, queries `User::paginate(10)`, suspend/restore via `SuspendUserAction`/`RestoreUserAction`, shows `isSuspended()` status |
| `events` | 59 | `WithPagination`, queries `Event::withoutGlobalScopes()->paginate(10)`, suspend/restore with reason via `SuspendEventAction`/`RestoreEventAction` |
| `settings` | 38 | `mount()` loads `PlatformSetting::current()`, form with `wire:model="settings.app_name"`, save via `UpdatePlatformSettingsAction` with `lockVersion` tracking |

All 4 components are real, functional, and wired to domain actions. Zero placeholders.

#### 5. EventApiController Uses Canonical SuspendEventRequest ✅

**Controller**: `EventApiController::suspend(SuspendEventRequest $request, ...)` (line 27)
**Request**: `SuspendEventRequest::rules()` → `['reason' => ['required', 'string', 'max:1000']]`
**Tested**: `AdminApiTest::allows platform_admin to suspend event` (line 128-138) and `allows super_admin to suspend event` (line 140-150)

#### 6. Full Artifact Alignment ✅

| Artifact | Status | Evidence |
|----------|--------|----------|
| proposal.md | ✅ Present | Scope, capabilities, out-of-scope items match implementation |
| 9 spec files | ✅ Present | 41 requirements, 72 scenarios — all covered by tests |
| design.md | ✅ Present | 6 architectural decisions — all faithfully implemented |
| tasks.md | ✅ Present | 18/18 tasks checked complete |
| apply-progress.md | ✅ Present | TDD cycle evidence for all test files |
| Implementation | ✅ | All Actions, Controllers, Models, middleware, routes, Volt components present |
| QA (tests + PHPStan + Pint + Rector) | ✅ | 928 passed, 0 PHPStan errors, Pint clean, Rector clean |
| docs/02-arquitectura/04-admin-platform.md | ✅ | Accurate |

### Coherence (Design)

| Decision | Implemented? | Evidence |
|----------|-------------|----------|
| Global Admin Context Middleware | ✅ | `EnsureGlobalAdminContext` sets team 0 in `handle()`, restores in `terminate()` |
| Authorization Matrix (super_admin vs platform_admin) | ✅ | `AssignGlobalRoleAction`/`RevokeGlobalRoleAction` check `hasRole('super_admin')`; `UserApiController::suspend/restore` gate on `super_admin` |
| User Suspension Storage (`suspended_at`) | ✅ | `users` table migration; `isSuspended()` + `active()` scope; token + session deletion on suspend |
| Reversible Event Suspension (previous_status) | ✅ | `SuspendEventAction` stores prior status; `RestoreEventAction` restores via `EventStatus::from()` |
| Platform Settings Singleton (lock_version) | ✅ | `UpdatePlatformSettingsAction` validates `lock_version` with dual-layer CAS; `PlatformSetting::current()` enforces single row |
| Admin Layout | ✅ | `layouts/admin.blade.php` with `admin-sidebar` navigation component |
| No automatic refund/payout on suspension | ✅ | Zero financial logic in suspend/restore actions; verified by EventModerationTest |
| GDPR deletion deferred | ✅ | No delete endpoint in UserApiController; DELETE returns 405 |
| Commission future-only application | ✅ | Payout snapshot at creation; historical payouts immutable |

### Issues Found

**CRITICAL**: None

**WARNING**: None

**SUGGESTION**: None

### Test Count Evolution

| Version | Tests | Assertions | PHPStan files | PHPStan errors |
|---------|-------|------------|---------------|----------------|
| v1 (original) | 915 | 2367 | — | — |
| v2 (warning remediation) | 922 | 2381 | 252 | 0 |
| v3 (this — fresh after blocker fixes) | 928 | 2395 | 252 | 0 |

+6 tests and +14 assertions since the v2 report, reflecting additional coverage added during the native review blocker fix cycle.

### Verdict

**PASS**

All 18 implementation tasks complete. All 928 tests pass with zero failures and 2395 assertions. PHPStan reports zero errors across 252 files. Pint and Rector are clean. All 72 spec scenarios are COMPLIANT with passing test coverage. All user-requested verification points confirmed: suspension invalidates tokens/sessions, last super_admin guard is concurrency-safe, PlatformSetting singleton + optimistic locking are enforced and tested, all 4 Volt admin components are functional, EventApiController uses canonical SuspendEventRequest, and all SDD artifacts (proposal, 9 specs, design, 18 tasks, implementation, tests, docs, apply-progress) remain aligned with zero contradictions.

**The change is ready for `sdd-archive`.** No source code modifications needed.

### Files Verified

| File | Purpose |
|------|---------|
| `app/Http/Middleware/EnsureGlobalAdminContext.php` | Team 0 isolation with restore |
| `app/Enums/EventStatus.php` | Added `Suspended` case (7 total) |
| `app/Models/User.php` | `suspended_at`, `isSuspended()`, `active()` scope |
| `app/Models/Event.php` | `previous_status`, `suspended_at`, `shouldBeSearchable()` |
| `app/Models/PlatformSetting.php` | Singleton, JSON settings, `lock_version`, `LogsActivity` |
| `app/Actions/Admin/Events/SuspendEventAction.php` | Prior status, reason required, activity log; 0 financial code |
| `app/Actions/Admin/Events/RestoreEventAction.php` | Restores `EventStatus::from(previous_status)`; 0 financial code |
| `app/Actions/Admin/Users/SuspendUserAction.php` | Last super_admin guard + lockForUpdate(), token/session revocation |
| `app/Actions/Admin/Users/RestoreUserAction.php` | Clear `suspended_at` |
| `app/Actions/Admin/Users/SendPasswordResetAction.php` | Password reset link |
| `app/Actions/Admin/Users/AssignGlobalRoleAction.php` | Super_admin gate, team 0 role creation |
| `app/Actions/Admin/Users/RevokeGlobalRoleAction.php` | Super_admin gate |
| `app/Actions/Admin/PlatformSettings/UpdatePlatformSettingsAction.php` | Dual-layer CAS lock check, activity log |
| `app/Actions/Payments/CreatePayoutAction.php` | Commission fallback chain; ??= preserves explicit zero |
| `app/Http/Controllers/Api/V1/Admin/UserApiController.php` | index, show (401/403/404/200), suspend, restore; NO delete |
| `app/Http/Controllers/Api/V1/Admin/EventApiController.php` | index, suspend (SuspendEventRequest), restore |
| `app/Http/Requests/Api/V1/Admin/SuspendEventRequest.php` | reason: required, string, max:1000 |
| `app/Http/Resources/Api/V1/Admin/UserResource.php` | is_suspended, suspended_at, global_roles |
| `app/Http/Resources/Api/V1/Admin/PlatformSettingResource.php` | settings, lock_version, updated_at |
| `app/Http/Requests/Api/V1/Admin/UpdatePlatformSettingsRequest.php` | lock_version + commission validation |
| `app/Http/Requests/Api/V1/Admin/AssignGlobalRoleRequest.php` | role enum validation |
| `app/Services/Discovery/EventSearchService.php` | published()+public() scopes exclude suspended |
| `bootstrap/app.php` | `global.admin` middleware alias registration |
| `config/permission.php` | Organizer team model, team 0 global context |
| `routes/web.php` | Admin Volt routes with role middleware |
| `routes/api.php` | API v1/admin routes — Sanctum + global.admin + role + throttle; NO delete |
| `resources/views/layouts/admin.blade.php` | Admin layout extending app-shell |
| `resources/views/components/navigation/admin-sidebar.blade.php` | Admin navigation |
| `resources/views/livewire/admin/dashboard.blade.php` | Functional dashboard component |
| `resources/views/livewire/admin/users.blade.php` | Functional user management component |
| `resources/views/livewire/admin/events.blade.php` | Functional event moderation component |
| `resources/views/livewire/admin/settings.blade.php` | Functional settings component |
| `resources/views/livewire/admin/reports/platform-hub.blade.php` | Reports component |
| `docs/02-arquitectura/04-admin-platform.md` | Admin architecture documentation |
| `database/migrations/*_add_suspended_at_to_users_table.php` | User suspended_at |
| `database/migrations/*_add_suspension_columns_to_event_table.php` | Event previous_status + suspended_at |
| `database/migrations/*_create_platform_settings_table.php` | Platform settings singleton table |

### Test Files Verified

| Test File | Tests | Purpose |
|-----------|-------|---------|
| `tests/Feature/AdminAuthorizationTest.php` | — | Team 0 isolation, tenant leak prevention |
| `tests/Feature/Admin/UserManagementTest.php` | — | Suspend/restore, last admin protection, roles, password reset |
| `tests/Feature/Admin/EventModerationTest.php` | — | Event suspend/restore, reason requirement, activity log, NO refund side effects |
| `tests/Feature/Admin/PlatformSettingsTest.php` | — | Settings update, concurrent rejection |
| `tests/Feature/Admin/AdminApiTest.php` | 15 | Auth, rate limiting, pagination, role gating, show 200/401/403/404, suspend/restore, GDPR 405 |
| `tests/Feature/Admin/SuspendedEventExclusionTest.php` | — | Search exclusion, Scout shouldBeSearchable |
| `tests/Feature/Admin/AdminUiTest.php` | — | Dashboard, users, events, settings — access control |
| `tests/Feature/Admin/CommissionFallbackTest.php` | — | Organizer→platform→hardcoded chain, explicit zero, historical immutability |
| `tests/Feature/Admin/FormRequestsTest.php` | — | SuspendEvent, UpdatePlatformSettings, AssignGlobalRole validation |
| `tests/Feature/Admin/ResourcesTest.php` | — | UserResource, PlatformSettingResource structure |
| `tests/Unit/Enums/EventStatusTest.php` | — | 7 cases including Suspended |
| `tests/Unit/PlatformSettingTest.php` | — | Singleton enforcement, current(), setting() |
| `tests/Unit/UserSuspensionTest.php` | — | Unit-level suspension coverage |
| `tests/Unit/EventSuspensionTest.php` | — | Unit-level suspension coverage |
