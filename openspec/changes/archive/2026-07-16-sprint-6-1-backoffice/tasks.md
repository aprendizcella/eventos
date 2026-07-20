# Tasks: Sprint 6.1 — Backoffice de Plataforma

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 650-900 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 auth+models → PR 2 actions/API → PR 3 Volt/UI+tests |
| Delivery strategy | exception-ok |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Auth + global team 0 isolation + additive schema | PR 1 | `vendor/bin/sail artisan test --compact --filter=AdminAuthorization` | N/A | middleware, permission config, migrations, enum/model casts |
| 2 | Actions/DTOs/FormRequests/Resources + payout commission fallback | PR 2 | `vendor/bin/sail artisan test --compact --filter=Commission` | N/A | admin actions, API payload layer, payout resolution |
| 3 | Volt admin UI + versioned API + catalog/search wiring | PR 3 | `vendor/bin/sail artisan test --compact --filter=Admin` | `vendor/bin/sail artisan route:list --path=api/v1/admin` | layouts, Volt components, routes/controllers |

## Phase 1: Authorization and Data Foundation

- [x] 1.1 Create `app/Http/Middleware/EnsureGlobalAdminContext.php` and register it in `bootstrap/app.php` to force `setPermissionsTeamId(0)` for admin requests.
- [x] 1.2 Update `config/permission.php` team resolution so global admin checks cannot inherit organizer context; keep organizer-scoped roles intact.
- [x] 1.3 Add additive migrations for `users.suspended_at`, `events.previous_status`/`events.suspended_at`, and new `platform_settings` singleton table with `lock_version` + JSON `settings`.
- [x] 1.4 Modify `app/Enums/EventStatus.php`, `app/Models/User.php`, `app/Models/Event.php`, and create `app/Models/PlatformSetting.php` with casts/scopes/helpers for the new lifecycle and settings rules.

## Phase 2: Domain Actions and HTTP Contracts

- [x] 2.1 Create admin/user actions for list/show/edit/suspend/restore/password-reset and global-role assignment with final `super_admin` protection and `platform_admin` 403 guard.
- [x] 2.2 Create event moderation actions for suspend/restore that store `previous_status`, require reason + actor, and keep catalog/search exclusion without financial side effects.
- [x] 2.3 Create platform settings DTOs, FormRequests, Resources, and update `CreatePayoutAction` resolution to use organizer → platform → hardcoded commission fallback for future payouts only.
- [x] 2.4 Add thin API contracts under `app/Http/Controllers/Api/V1/Admin/`, `Requests`, and `Resources` for paginated, consistent admin JSON responses.

## Phase 3: Admin UI and API Wiring

- [x] 3.1 Add `resources/views/layouts/admin.blade.php` and the admin route shell in `routes/web.php` with the dedicated backoffice navigation.
- [x] 3.2 Build Volt dashboard/user/event/settings components in `resources/views/livewire/admin/` and wire them to the new actions.
- [x] 3.3 Add `/api/v1/admin/*` routes in `routes/api.php` with Sanctum, explicit global context middleware, rate limits, and uniform error envelopes.
- [x] 3.4 Update catalog/search queries and event indexing paths so suspended events are excluded everywhere by default.

## Phase 4: Strict TDD Verification

- [x] 4.1 Write RED tests for every authorization case: team 0 isolation, super_admin vs platform_admin, unauthenticated API 401, and cross-tenant denial.
- [x] 4.2 Write RED tests for user suspension, restore, password reset, and last-active `super_admin` protection.
- [x] 4.3 Write RED tests for reversible event suspension, previous-status restore, mandatory reason/actor, and no refund/payout side effects.
- [x] 4.4 Write RED tests for settings concurrency, validation, commission fallback precedence, future-only payout application, catalog/search exclusion, and admin UI/API pagination and errors.

## Phase 5: Cleanup and Consistency

- [x] 5.1 Normalize docblocks, route names, and resource payloads to match existing Laravel conventions and keep admin responses consistent.
- [x] 5.2 Remove temporary scaffolding and confirm all new files are covered by the targeted Pest suite before implementation handoff.
