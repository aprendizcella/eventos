## Exploration: Sprint 6.1 — Backoffice de Plataforma

### Current State
- **Roles**: Spatie `laravel-permission` is installed with `teams=true` and `organizer_id` as the team foreign key. Global roles `super_admin` and `platform_admin` are already seeded and used across policies (EventPolicy, VenuePolicy, OrganizerPolicy) and existing admin routes (`/admin/reports`).
- **Admin routes**: Already exist in `routes/web.php` under prefix `admin` with middleware `['role:super_admin|platform_admin']`. Only the platform report hub (`admin.reports.platform-hub`) is implemented so far.
- **Volt pattern**: Single-file components live in `resources/views/livewire/{domain}/{component}.blade.php`. PHP logic sits in `new class extends Component` at the top, Blade markup below. No separate Livewire PHP classes exist.
- **Layout**: `layouts/app.blade.php` wraps everything in `<x-layout.app-shell>` (sidebar + topbar). There is no dedicated admin layout yet.
- **Settings storage**: Organizer and event settings are stored as JSON in `organizers.settings` and `events.settings`. There is no global platform settings table; platform-wide commission logic currently reads from organizer-level `billing` JSON.
- **API structure**: `routes/api.php` uses Controllers under `App\Http\Controllers\Api\V1\`, with `auth:sanctum` + `organizer.detect`. The existing API already has a pattern for admin bypass (`hasRole(['super_admin', 'platform_admin'])`).
- **Dashboard/table patterns**: Organizer dashboard uses KPI cards + quick links. `events-table` provides a rich reusable pattern: search, filters, sortable columns, pagination, CSV export, delete confirmation modal, and column visibility toggle.

### Affected Areas
- `routes/web.php` — add new admin Volt routes under existing `admin` prefix.
- `routes/api.php` — add `/api/v1/admin/*` routes for programmatic admin access.
- `resources/views/layouts/admin.blade.php` — new layout extending `app.blade.php` or reusing `app-shell` with admin navigation.
- `resources/views/livewire/admin/` — new Volt components: `admin-dashboard`, `user-management`, `moderate-events`, `platform-settings`.
- `app/Policies/EventPolicy.php`, `app/Policies/OrganizerPolicy.php` — already allow `platform_admin`; may need minor additions for bulk operations.
- `app/Http/Controllers/Api/V1/AdminApiController.php` (or similar) — new API endpoints.
- `database/migrations/` — likely need a `platform_settings` or `settings` table for global configuration.

### Approaches
1. **Settings: JSON column on a singleton model** — Store global settings in a single-row `platform_settings` table (key/value or JSON blob), similar to organizer `settings`.
   - Pros: Consistent with existing JSON settings pattern; easy to read/write; no schema changes for new settings.
   - Cons: No type safety at DB level; validation must happen in app layer.
   - Effort: Low

2. **Settings: Dedicated typed table** — Create `platform_settings` with typed columns (e.g., `commission_percentage`, `commission_fixed`).
   - Pros: Type safety, queryable, migrations document changes.
   - Cons: More rigid; every new setting requires a migration.
   - Effort: Medium

3. **API: Thin Controllers + Actions** — Follow existing API pattern: Controller validates, delegates to Action, returns Resource/JsonResponse.
   - Pros: Matches codebase (`EventApiController` uses `CheckInAttendeeAction`); keeps Controllers thin; testable.
   - Cons: Slightly more files.
   - Effort: Low-Medium

4. **API: Volt/Livewire only (no API)** — Use Volt components for all admin interactions, skip REST API.
   - Pros: Less code; consistent with existing admin report hub.
   - Cons: Does not provide programmatic access; harder to test headlessly.
   - Effort: Low

### Recommendation
- **Layout**: Create `layouts/admin.blade.php` extending `app.blade.php` but overriding the sidebar slot with an admin-specific navigation (reusing `<x-layout.app-shell>`).
- **Volt components**: Follow the `organizers/events-table.blade.php` pattern for `user-management` and `moderate-events` (search, filters, pagination, modals). Follow `organizers/settings.blade.php` for `platform-settings` (tabs, validation, flash messages).
- **Settings**: Use a single-row `platform_settings` table with a `settings` JSON column ( Approach 1 ) to stay consistent with organizer/event settings. Create a `PlatformSetting` model.
- **API**: Add `/api/v1/admin/*` routes using thin Controllers + Actions (Approach 3), protected by `auth:sanctum` + custom middleware ensuring `role:super_admin|platform_admin`. The middleware can be registered in `bootstrap/app.php` alongside existing aliases.
- **Role checks**: Reuse existing `role:super_admin|platform_admin` middleware for web routes and `hasRole(['super_admin', 'platform_admin'])` for API authorization. In tests, remember to call `setPermissionsTeamId(0)` before assigning global roles.

### Risks
- **Spatie team context**: Global roles (`super_admin`, `platform_admin`) require `PermissionRegistrar::setPermissionsTeamId(0)` in tests and any background jobs. Forgetting this breaks authorization.
- **Route collisions**: The `admin` prefix is already used. New routes must not clash with existing `/admin/reports`.
- **Settings scope**: Platform settings should not be confused with organizer settings. The `CommissionCalculator` currently reads from `BillingSettings` derived from organizer JSON. Changing this to read global defaults when organizer settings are absent requires careful wiring.
- **No existing user CRUD**: `user-management` is greenfield. There is no prior art for cross-organizer user listing or role assignment UI.

### Ready for Proposal
**Yes.** The orchestrator should tell the user:
- We will reuse the existing `admin` route prefix and `role:super_admin|platform_admin` middleware.
- We will create a dedicated `layouts/admin.blade.php` and four Volt components under `livewire/admin/`.
- We will add a `platform_settings` table (JSON column) for global commission and platform configuration.
- We will expose `/api/v1/admin/*` endpoints via thin Controllers invoking Actions, following the existing `Api/V1` pattern.
- Next phase: `sdd-propose` to lock scope and delivery strategy.
