# Proposal: Sprint 1.1 Setup and Auth

## Intent

Establish the project authentication foundation for HI.EVENTS: package-backed auth, web login/register/recovery flows, role basics, and audit visibility, while keeping business behavior in Actions/Controllers and Volt as thin presentation.

## Scope

### In Scope
- Add and configure approved Sprint 1.1 auth/support packages during apply: Sanctum, Spatie Permission, Spatie Activitylog, Purifier, Livewire/Volt.
- Implement web auth flows: register, login, logout, password reset, and email-verification infrastructure that does not block Sprint 1.1 access.
- Seed basic roles: `super_admin`, `platform_admin`, `organizer_admin`, `organizer_editor`, `organizer_viewer`, `attendee`.
- Record explicit auth activities where package traits do not cover login/logout/register.
- Add Pest coverage for auth flows, role availability, authorization checks, and activity logging.

### Out of Scope
- Installing packages or implementing code in this proposal phase.
- API token issuance endpoints beyond package readiness.
- Blocking access on verified email.
- Renaming vendor tables or changing existing `users.id` conventions.

## Capabilities

### New Capabilities
- `user-authentication`: registration, session login/logout, password reset, and non-blocking verification readiness.
- `role-based-access`: initial roles and permission middleware readiness.
- `auth-audit-logging`: auditable auth/security events.

### Modified Capabilities
- None; no existing OpenSpec specs are present.

## Approach

Use backend-first Actions + DTOs + FormRequests + Controllers for auth writes; keep Volt components/layouts presentation-only. Accept package migration table names. Slice implementation for review: dependencies/config, auth domain, web UI, roles/audit, tests.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `composer.json`, `composer.lock` | Modified | Add approved packages in apply phase. |
| `config/`, `bootstrap/app.php` | New/Modified | Publish config; register Laravel 12 middleware/aliases. |
| `app/Models/User.php` | Modified | Add tokens, roles, activity logging, verification readiness. |
| `app/Actions/Auth/`, `app/Http/*/Auth/` | New | Application/business auth core. |
| `resources/views/`, `routes/web.php` | New/Modified | Thin Volt auth UI and routes. |
| `database/migrations/`, `database/seeders/` | New/Modified | Vendor migrations and initial roles. |
| `tests/Feature/`, `tests/Unit/` | New/Modified | Pest coverage. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Sprint exceeds 800-line review budget | High | Require sliced PR plan before apply. |
| Laravel 12 middleware misconfiguration | Med | Configure in `bootstrap/app.php`; cover with feature tests. |
| Activitylog misses login/logout | Med | Record explicit auth activities. |

## Rollback Plan

Revert Sprint 1.1 commits by slice. If migrations ran, roll back vendor/auth migrations before removing package config, middleware aliases, traits, routes, and UI files.

## Dependencies

- User approval before package installation in apply phase.
- Compatible Laravel 12/PHP 8.4 package resolution.

## Success Criteria

- [ ] Auth flows work through Actions/Controllers with thin Volt UI.
- [ ] Six initial roles exist and are test-covered.
- [ ] Email verification infrastructure exists but does not block access.
- [ ] Auth activity is recorded and verified by Pest tests.
- [ ] Review slicing strategy is ready before implementation.
