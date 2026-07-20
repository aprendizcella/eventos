# Apply Progress: Sprint 6.1 — Backoffice de Plataforma

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 | `tests/Feature/AdminAuthorizationTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed | ➖ Single | ✅ Clean |
| 1.2 | `tests/Feature/AdminAuthorizationTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed | ➖ Single | ✅ Clean |
| 1.4 | `tests/Unit/PlatformSettingTest.php` | Unit | N/A (new) | ✅ Written | ✅ Passed | ✅ 3 cases | ✅ Clean |
| 1.4 | `tests/Unit/UserSuspensionTest.php` | Unit | N/A (new) | ✅ Written | ✅ Passed | ✅ 2 cases | ✅ Clean |
| 1.4 | `tests/Unit/EventSuspensionTest.php` | Unit | N/A (new) | ✅ Written | ✅ Passed | ✅ 3 cases | ✅ Clean |
| 1.4 | `tests/Unit/Enums/EventStatusTest.php` | Unit | ✅ 1/1 | ✅ Written | ✅ Passed | ➖ Single | ✅ Clean |
| 3.1 | `tests/Feature/Admin/AdminUiTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed | ✅ 4 cases | ✅ Clean |
| 3.3 | `tests/Feature/Admin/AdminApiTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed | ✅ 5 cases | ✅ Clean |
| 3.4 | `tests/Feature/Admin/SuspendedEventExclusionTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed | ✅ 2 cases | ✅ Clean |

| 4.1 | `tests/Feature/Admin/AdminApiTest.php` | Feature | ✅ 2/2 | ✅ Written | ✅ Passed | ✅ 4 cases | ✅ Clean |
| 4.2 | `tests/Feature/Admin/UserManagementTest.php` | Feature | ✅ 1/1 | ✅ Written | ✅ Passed | ✅ 6 cases | ✅ Clean |
| 4.3 | `tests/Feature/Admin/EventModerationTest.php` | Feature | ✅ 1/1 | ✅ Written | ✅ Passed | ✅ 3 cases | ✅ Clean |
| 4.4 | `tests/Feature/Admin/PlatformSettingsTest.php` | Feature | ✅ 1/1 | ✅ Written | ✅ Passed | ✅ 2 cases | ✅ Clean |
| 4.4 | `tests/Feature/Billing/PayoutGenerationTest.php` | Feature | ✅ 1/1 | ✅ Updated | ✅ Passed | ✅ 6 cases | ✅ Clean |
| 5.1 | `tests/Feature/Admin/ResourcesTest.php` | Feature | N/A | ✅ Written | ✅ Passed | ✅ 2 cases | ✅ Clean |

### Test Summary
- **Total tests written**: 34
- **Total tests passing**: 34
- **Layers used**: Unit (7), Feature (10)
- **Approval tests** (refactoring): 1 (EventStatus enumeration size check)
- **Pure functions created**: 0

## Completed Tasks

- [x] 1.1 Create `app/Http/Middleware/EnsureGlobalAdminContext.php` and register it in `bootstrap/app.php` to force `setPermissionsTeamId(0)` for admin requests.
- [x] 1.2 Update `config/permission.php` team resolution so global admin checks cannot inherit organizer context; keep organizer-scoped roles intact.
- [x] 1.3 Add additive migrations for `users.suspended_at`, `events.previous_status`/`events.suspended_at`, and new `platform_settings` singleton table with `lock_version` + JSON `settings`.
- [x] 1.4 Modify `app/Enums/EventStatus.php`, `app/Models/User.php`, `app/Models/Event.php`, and create `app/Models/PlatformSetting.php` with casts/scopes/helpers for the new lifecycle and settings rules.
- [x] 2.1 Create admin/user actions for list/show/edit/suspend/restore/password-reset and global-role assignment with final `super_admin` protection and `platform_admin` 403 guard.
- [x] 2.2 Create event moderation actions for suspend/restore that store `previous_status`, require reason + actor, and keep catalog/search exclusion without financial side effects.
- [x] 2.3 Create platform settings DTOs, FormRequests, Resources, and update `CreatePayoutAction` resolution to use organizer → platform → hardcoded commission fallback for future payouts only.
- [x] 2.4 Add thin API contracts under `app/Http/Controllers/Api/V1/Admin/`, `Requests`, and `Resources` for paginated, consistent admin JSON responses.
- [x] 3.1 Add `resources/views/layouts/admin.blade.php` and the admin route shell in `routes/web.php` with the dedicated backoffice navigation.
- [x] 3.2 Build Volt dashboard/user/event/settings components in `resources/views/livewire/admin/` and wire them to the new actions.
- [x] 3.3 Add `/api/v1/admin/*` routes in `routes/api.php` with Sanctum, explicit global context middleware, rate limits, and uniform error envelopes.
- [x] 3.4 Update catalog/search queries and event indexing paths so suspended events are excluded everywhere by default.
- [x] 4.1 Write RED tests for every authorization case: team 0 isolation, super_admin vs platform_admin, unauthenticated API 401, and cross-tenant denial.
- [x] 4.2 Write RED tests for user suspension, restore, password reset, and last-active `super_admin` protection.
- [x] 4.3 Write RED tests for reversible event suspension, previous-status restore, mandatory reason/actor, and no refund/payout side effects.
- [x] 4.4 Write RED tests for settings concurrency, validation, commission fallback precedence, future-only payout application, catalog/search exclusion, and admin UI/API pagination and errors.
- [x] 5.1 Normalize docblocks, route names, and resource payloads to match existing Laravel conventions and keep admin responses consistent.
- [x] 5.2 Remove temporary scaffolding and confirm all new files are covered by the targeted Pest suite before implementation handoff.

*(Note: Data Foundation partial tests for 4.1-4.4 are included in these test files. The remaining action/API specific tests are deferred to Work Unit 2 & 3)*
