## Exploration: sprint-1-1-setup-auth

### Current State
The repository is still at a fresh Laravel 12/PHP 8.4 baseline. `composer.json` only requires `laravel/framework` and `laravel/tinker`; none of the Sprint 1.1 packages are installed. `app/Models/User.php` is the default authenticatable model with `HasFactory` and `Notifiable` only, no `MustVerifyEmail`, Sanctum tokens, roles, or activity logging. `routes/web.php` only serves the welcome page, `bootstrap/app.php` has no middleware configuration, migrations are only the Laravel defaults (`users`, password reset tokens, sessions, cache, jobs), and tests are default PHPUnit-style examples although Pest 4 is installed.

Sprint 1.1 is therefore a foundational change, not an incremental auth tweak. It must introduce package installation/configuration, database migrations, auth flows, frontend layout/Volt components, and Pest coverage in controlled slices.

### Affected Areas
- `composer.json` / `composer.lock` — add Phase 1 runtime packages and lock compatible versions.
- `config/sanctum.php`, `config/cors.php`, `config/permission.php`, `config/activitylog.php`, `config/purifier.php` — vendor-published configuration requiring project-specific hardening.
- `bootstrap/app.php` — Laravel 12 middleware registration point; required for Sanctum stateful API middleware and Spatie route middleware aliases.
- `app/Models/User.php` — add `HasApiTokens`, `HasRoles`, `LogsActivity`; likely implement email verification and activity log options.
- `database/migrations/` — add Sanctum `personal_access_tokens`, Spatie Permission, Activitylog, and possible user table adjustments. Existing users table uses Laravel default plural `users`, not the proposal's singular-table convention.
- `app/Actions/Auth/`, `app/DataTransferObjects/Auth/`, `app/Http/Requests/Auth/`, `app/Http/Controllers/Auth/` — create write-flow pieces if choosing controller/action auth instead of pure Volt handlers.
- `resources/views/layouts/` and `resources/views/livewire/auth/` — create base layout and Volt auth UI.
- `routes/web.php` and possibly `routes/api.php` — register web auth pages/actions and future Sanctum API token endpoints; `routes/api.php` does not exist yet.
- `tests/Feature/`, `tests/Unit/`, and optionally `tests/Browser/` — replace default-style coverage with Pest tests for auth, roles/permissions, activity logging, and Livewire/Volt behavior.

### Approaches
1. **Backend-first Actions + thin Volt UI** — Implement auth business behavior in Actions/DTOs/FormRequests/Controllers, then have Volt components call the same Actions or route to controllers.
   - Pros: Consistent with boilerplate flow (`FormRequest → DTO → Controller → Action`), testable without browser/UI coupling, keeps Livewire as presentation only, supports future API reuse.
   - Cons: More files and more planning up front; some Laravel auth primitives may feel duplicated if not carefully scoped.
   - Effort: Medium/High

2. **Volt-centric auth flow** — Build login/register/reset/verification directly as Volt components using Livewire validation and Laravel auth services.
   - Pros: Faster MVP UI delivery, fewer controllers/requests, natural fit for Livewire-first UX.
   - Cons: Risks putting business/auth orchestration in components, weaker alignment with documented architecture, harder to reuse for API/mobile later.
   - Effort: Medium

3. **Install a starter kit and adapt** — Use a Laravel starter auth scaffold as source of auth flows, then retrofit package traits and project conventions.
   - Pros: Fastest route to complete auth screens and tests.
   - Cons: High convention mismatch risk, broad generated diff, may violate the review budget and introduce patterns not approved by this boilerplate.
   - Effort: High cleanup risk

### Recommendation
Use **Backend-first Actions + thin Volt UI**, delivered in small slices:

1. Baseline/compatibility slice: dry-run dependency resolution, add packages, publish configs/migrations, and verify app boots.
2. Package configuration slice: Sanctum middleware/config, Permission middleware/cache, Activitylog defaults, Purifier strict profiles, Volt installation.
3. User model/auth domain slice: traits, email verification decision, activity log options, Auth DTOs/FormRequests/Actions.
4. Web auth slice: routes/controllers or route-backed actions plus base layout and Volt auth components.
5. Authorization/audit slice: seed/define initial roles and permissions, ensure auth events/activity are recorded.
6. Test slice: Pest feature tests for register/login/logout/reset/verification, permission checks, activity log assertions, and Livewire/Volt component tests where UI behavior matters.

This order keeps risky dependency/config work isolated before domain/UI work. It also protects the 800-line review budget: Sprint 1.1 is likely too large for one PR unless tightly sliced.

Package/version compatibility findings from Packagist/docs:
- `laravel/sanctum` v4.3.2 supports PHP `^8.2` and Laravel `^11|^12|^13` components.
- `spatie/laravel-permission` v8.0.0 is documented as PHP `^8.3` and Laravel `^12|^13` compatible.
- `spatie/laravel-activitylog` v5.0.0 is documented as PHP `^8.4` and Laravel `^12|^13` compatible.
- `mews/purifier` v3.4.4 supports Laravel `^12|^13` components and pulls `ezyang/htmlpurifier ^4.16`; risk remains in the underlying legacy purifier library.
- `livewire/livewire` v4 and `livewire/volt` v1 are documented as Laravel 12 compatible, but Livewire v4 is comparatively new and should be pinned/dry-run verified.

Decisions needed before proposal/spec:
- Whether Sprint 1.1 includes full email verification now (`MustVerifyEmail`, routes, notifications) or only scaffolds it.
- Whether auth writes are controller/action-backed, Volt-only, or hybrid; recommended: controller/action-backed with thin Volt UI.
- Whether to add `routes/api.php` in Sprint 1.1 for Sanctum token auth, or defer API token endpoints to Sprint 1.4.
- Whether initial roles/permissions are seeded in this sprint, and the exact canonical role names (`super_admin`, `platform_admin`, `organizer_admin`, `organizer_editor`, `organizer_viewer`, `attendee`).
- Whether to keep Laravel default `users.id` for the existing table or migrate toward the proposal's `{model}_id` convention; changing the existing users PK now would be disruptive and should require explicit approval.
- Which Activitylog events must count for the acceptance criterion “Actividad de auth se registra”: model changes only, explicit login/logout/register activities, or both.
- Whether package-generated migrations may keep their vendor table names (`roles`, `permissions`, `activity_log`, `personal_access_tokens`) despite the project’s singular-table convention.

### Risks
- Sprint 1.1 scope is broad and likely exceeds the 800-line review budget if implemented as one PR.
- Generated package migrations/configs may conflict with local conventions such as singular table names and custom primary key naming.
- Existing tests are PHPUnit-style classes; new Sprint tests should be Pest-style, creating temporary mixed style unless defaults are converted.
- Laravel 12 middleware must be registered in `bootstrap/app.php`, not `app/Http/Kernel.php`; misconfiguration will break Sanctum/Permission behavior.
- `mews/purifier` depends on `ezyang/htmlpurifier`; PHP 8.5+ deprecation/security monitoring remains necessary.
- Activity logging login/logout is not automatic from `LogsActivity`; explicit auth activity recording may be needed to satisfy acceptance criteria.
- Email verification and password reset need mail/notification behavior tested with fakes to avoid brittle tests.

### Ready for Proposal
Yes — proceed to `sdd-propose`, but the orchestrator should tell the user that Sprint 1.1 must be sliced and that several decisions above need approval before spec/design. The safest proposal should explicitly avoid installing packages until the apply phase and should define reviewable work units.
