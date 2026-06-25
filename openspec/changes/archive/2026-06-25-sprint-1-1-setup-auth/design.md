# Design: Sprint 1.1 Setup and Auth

## Technical Approach

Implement auth as backend-first Laravel 12 flows: FormRequests validate and build DTOs, Controllers coordinate single-purpose Actions, and Volt renders thin auth pages only. Apply will install/configure `laravel/sanctum`, `spatie/laravel-permission`, `spatie/laravel-activitylog`, `mews/purifier`, `livewire/livewire`, and `livewire/volt` only after approval. Package migrations/config remain vendor-conventional; Laravel 12 middleware aliases and auth rate limiters are registered through Laravel 12 entry points.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Auth core | Actions + DTOs + FormRequests + Controllers | Volt components owning auth logic; Breeze/Jetstream scaffolding | Matches repo architecture rules and keeps UI replaceable. |
| Package integration | Publish config/migrations, keep vendor table names | Rename permission/activity tables | Reduces package drift and rollback risk. |
| Middleware | Add Spatie aliases in `bootstrap/app.php` via `withMiddleware()` | Legacy `Http\Kernel`; per-route closures | Laravel 12 has no Kernel; aliases are testable and reusable. |
| Audit boundary | Wrap explicit `auth` log writes behind a safe audit Action | Let Activitylog exceptions bubble; catch broadly in controllers | Auth success/failure must not reveal logging internals or secrets. |
| Throttling | Named rate limiters for login and password reset request POST routes | Rely on custom validation only | Prevents brute-force and reset-email abuse using Laravel routing conventions. |
| Purifier | Configure/prove readiness only; do not purify credentials | Sanitize every plain auth input | Rich HTML belongs to later event/organizer fields; auth fields are validated/escaped and passwords/tokens must remain untouched. |

## Data Flow

Guest form → Volt Blade view → named web route → FormRequest → DTO → Controller → Action
Action → User/Auth/Password broker → safe audit logger → redirect/session response

Audit failure boundary: safe audit logger catches Activitylog/logging exceptions, reports sanitized context, and returns without changing the auth response. No exception message, stack trace, password, reset token, raw payload, or internal logger detail reaches session flash/errors.

Role checks: route middleware alias → Spatie Permission → `User::hasRole()`/permissions. Throttled auth submissions use named route middleware such as `throttle:login` and `throttle:password-reset-request`.

## File Changes

| File | Action | Description |
|---|---|---|
| `composer.json`, `composer.lock` | Modify | Add approved packages during apply only. |
| `config/sanctum.php`, `permission.php`, `activitylog.php`, `purifier.php`, `livewire.php` | Create | Published package configuration; no secrets. |
| `bootstrap/app.php` | Modify | Register `role`, `permission`, `role_or_permission` middleware aliases and route-level rate limiters/exception hooks as needed. |
| `app/Models/User.php` | Modify | Add `HasApiTokens`, `HasRoles`, `LogsActivity`, verification readiness (`MustVerifyEmail` import kept non-blocking), safe activity options. |
| `app/DataTransferObjects/Auth/*` | Create | Immutable DTOs named `RegisterUserDto`, `LoginUserDto`, `RequestPasswordResetDto`, `ResetPasswordDto` (no `*Data` classes). |
| `app/Actions/Auth/*` | Create | Register, login, logout, reset request, reset password, and isolated audit logging orchestration. |
| `app/Http/Requests/Auth/*` | Create | Validation plus `toDto()`; controllers never call `validated()` directly. |
| `app/Http/Controllers/Auth/*` | Create | Invokable thin controllers returning redirects/views. |
| `routes/web.php` | Modify | Named guest/auth routes; login and reset-link POST routes attach explicit throttle middleware. |
| `resources/views/layouts/auth.blade.php`, `resources/views/livewire/auth/*` | Create | Minimal Volt UI/layout with no business rules. |
| `database/seeders/RoleSeeder.php`, `DatabaseSeeder.php` | Create/Modify | Idempotently seed six roles with `firstOrCreate(['name' => ..., 'guard_name' => 'web'])`. |
| `tests/Feature/Auth/*`, `tests/Feature/Authorization/*`, `tests/Feature/Audit/*` | Create | Pest coverage for specs. |

## Interfaces / Contracts

DTOs are final readonly PHP classes with typed properties and `Dto` suffix. FormRequests expose concrete `toDto()` returns such as `RegisterUserDto` and `LoginUserDto`. Actions implement `__invoke(Dto $data): mixed` and never return HTTP responses.

Auth audit properties allow only privacy-safe metadata: event name, subject user id, optional IP/user-agent hash, and outcome. The audit Action must sanitize allowlisted properties before calling Activitylog and catch `Throwable` around the logging call only. Fallback behavior: report a generic sanitized warning through Laravel logging/reporting and continue the auth flow response unchanged.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Unit | DTO mapping and auth audit payload filtering | Pest unit tests; datasets for sensitive keys. |
| Feature | Register, login, logout, reset request/completion, non-blocking verification, throttling | Laravel feature tests with `LazilyRefreshDatabase`, notifications/mail fakes, and repeated requests asserting throttle behavior. |
| Integration | Role seeding, middleware allow/deny, activity records, audit failure isolation | Seed roles, define representative protected test routes, fake/force Activitylog failure, assert auth response stays safe and secrets are absent. |
| UI smoke | Volt auth pages render and submit to backend routes | Pest feature/browser smoke depending final package availability. |

Package readiness tests should assert config/classes are available after installation, not token endpoints. Done criteria must run the repo QA pipeline through Sail/project scripts: `vendor/bin/sail composer run rector`, `vendor/bin/sail composer run pint`, `vendor/bin/sail composer run phpstan`, `vendor/bin/sail composer run test`, then host Sonar (`./sonar.sh`) when available/documented.

## Migration / Rollout

No data migration beyond vendor/auth package migrations. Roll out in review slices under 800 changed lines: (1) dependencies/config/middleware, (2) auth backend Actions/DTOs/Requests/Controllers, (3) Volt views/routes, (4) roles/audit, (5) tests/QA fixes. Roll back each slice in reverse; if migrations ran, rollback package migrations before removing traits/config/routes.

## Open Questions

- [ ] Package installation still requires explicit approval before apply.
