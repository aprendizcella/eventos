# Tasks: Sprint 1.1 Setup and Auth

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 1,600-2,400 total; target 350-750 per slice |
| 800-line budget risk | High |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 packages/config → PR 2 backend auth → PR 3 Volt UI → PR 4 roles/audit → PR 5 Pest/QA |
| Delivery strategy | ask-always |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units / Commit Boundaries

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Approved dependencies/config | PR 1 | Requires explicit package-install approval before apply; rollback packages/config/migrations. |
| 2 | Backend auth core | PR 2 | DTOs, FormRequests, Actions, Controllers, `User`; tests with behavior. |
| 3 | Volt auth UI | PR 3 | `routes/web.php`, auth layout, Volt views; thin UI only. |
| 4 | Roles and audit | PR 4 | Role seeder, middleware readiness, safe audit boundary. |
| 5 | QA hardening | PR 5 | Full Pest coverage, throttling, quality pipeline, safety checklist. |

## Phase 0: Approval Gate

- [x] 0.1 Confirm package install approval for Sanctum, Spatie Permission, Activitylog, Purifier, Livewire, Volt; do not modify `composer.json` before approval.
- [x] 0.2 Confirm chain strategy before `sdd-apply`: stacked-to-main, feature-branch-chain, or size exception.

## Phase 1: Packages and Configuration (RED → GREEN)

- [x] 1.1 RED: add package-readiness tests for config/classes and “no token endpoint” in `tests/Feature/Auth/PackageReadinessTest.php`.
- [x] 1.2 GREEN: after approval only, update `composer.json`/`composer.lock`, publish vendor configs/migrations, and register aliases/rate limiters in `bootstrap/app.php`.
- [x] 1.3 REFACTOR: verify `config/sanctum.php`, `config/permission.php`, `config/activitylog.php`, `config/purifier.php`, `config/livewire.php` contain no secrets.

## Phase 2: Auth Backend Core

- [x] 2.1 RED: add feature tests for register/login/logout/reset/verification readiness and invalid credentials in `tests/Feature/Auth/*Test.php`.
- [x] 2.2 GREEN: create `app/DataTransferObjects/Auth/*Dto.php` and `app/Http/Requests/Auth/*Request.php` with `toDto()` only.
- [x] 2.3 GREEN: create `app/Actions/Auth/*Action.php` and thin invokable `app/Http/Controllers/Auth/*Controller.php`.
- [x] 2.4 GREEN: update `app/Models/User.php` with package traits and non-blocking verification readiness.

## Phase 3: Volt UI and Routes

- [x] 3.1 RED: add UI smoke tests for auth pages and backend submissions.
- [x] 3.2 GREEN: update `routes/web.php` with named guest/auth routes and `throttle:login` / `throttle:password-reset-request` on POST routes.
- [x] 3.3 GREEN: create `resources/views/layouts/auth.blade.php` and `resources/views/livewire/auth/*` with presentation-only Volt forms.

## Phase 4: Roles, Middleware, and Audit Boundary

- [x] 4.1 RED: add role seeding and allow/deny middleware tests in `tests/Feature/Authorization/*Test.php`.
- [x] 4.2 GREEN: create `database/seeders/RoleSeeder.php` and update `DatabaseSeeder.php` with idempotent six-role seeding.
- [x] 4.3 RED: add audit privacy/failure tests in `tests/Feature/Audit/*Test.php` and sensitive-key datasets in `tests/Unit/Auth/*Test.php`.
- [x] 4.4 GREEN: create safe audit Action used by auth Actions; catch logging failures, report sanitized context, never expose secrets.

## Phase 5: Pest, QA, and Safety

- [x] 5.1 Verify throttling by repeated POST attempts for login and reset request; assert safe validation feedback.
- [x] 5.2 Run focused checks per slice: `vendor/bin/sail composer run test -- --filter=Auth`, then relevant Authorization/Audit filters.
- [x] 5.3 Run final QA: `vendor/bin/sail composer run rector`, `vendor/bin/sail composer run pint`, `vendor/bin/sail composer run phpstan`, `vendor/bin/sail composer run test`, then `./sonar.sh` if available.
- [x] 5.4 QA checklist: no `.env`, no raw secrets in audit data, no business logic in Volt, controllers remain thin, no package/table renames.

## Rollback Notes

- Roll back PRs in reverse order. If migrations ran, rollback vendor/auth migrations before removing traits, middleware aliases, routes, config, and UI files.
