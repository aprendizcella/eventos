## Exploration: Sprint 1.2 — Organizadores y Equipos

### Current State

Sprint 1.1 (Auth/Roles/Audit) and UX Foundation are complete and verified. The codebase has:

- **User model** with `HasRoles` (Spatie Permission), `LogsActivity`, `HasApiTokens`, `MustVerifyEmail`.
- **Six roles seeded** idempotently: `super_admin`, `platform_admin`, `organizer_admin`, `organizer_editor`, `organizer_viewer`, `attendee`.
- **Architecture patterns established**: Action-based writes (DTO → Action), FormRequest validation with `toDto()`, thin Controllers, Volt for presentation.
- **Admin layout** with sidebar (`app-shell`, `sidebar`, `topbar`), dark/light mode, and placeholder dashboard.
- **Activity logging** configured for auth events; Spatie Activitylog tables exist.
- **Testing**: Pest 4.x with LazilyRefreshDatabase; feature tests for auth, roles, audit, and admin layout.

There is **no Organizer domain** yet. The sidebar has a disabled "Organizers" link. The dashboard shows "Active Organizers: 0".

---

### Domain Model Analysis

#### Entities Needed

1. **Organizer**
   - Represents an organization that creates and manages events.
   - Has branding, contact info, and configuration.
   - Owned by a User (the creator), but membership is managed through `organizer_user`.

2. **OrganizerUser (pivot)**
   - Links User to Organizer with an organizer-scoped role.
   - Roles stored here: `admin`, `editor`, `viewer` (distinct from global Spatie roles).
   - Enables a user to belong to multiple organizers with different roles.

3. **User (existing)**
   - Global authentication entity.
   - Can belong to many organizers via `organizer_user`.
   - Global roles (`super_admin`, `platform_admin`) are platform-level; organizer roles are pivot-level.

#### Relationship Diagram

```
User 1 -----* organizer_user *----- 1 Organizer
              |
              +-- role (admin|editor|viewer)
              +-- invited_by_user_id (nullable)
              +-- joined_at
```

---

### Proposed Database Schema

#### Table: `organizer`

| Column | Type | Constraints |
|--------|------|-------------|
| `organizer_id` | `BIGINT UNSIGNED PK` | Auto-increment |
| `name` | `VARCHAR(255)` | NOT NULL |
| `slug` | `VARCHAR(255)` | UNIQUE, NOT NULL |
| `description` | `TEXT` | nullable |
| `logo_url` | `VARCHAR(512)` | nullable |
| `primary_color` | `VARCHAR(7)` | nullable (hex) |
| `website` | `VARCHAR(255)` | nullable |
| `email` | `VARCHAR(255)` | nullable |
| `phone` | `VARCHAR(50)` | nullable |
| `is_active` | `BOOLEAN` | DEFAULT true |
| `created_by_user_id` | `BIGINT UNSIGNED FK → users.id` | nullable, SET NULL on delete |
| `created_at` / `updated_at` | `TIMESTAMP` | — |
| `deleted_at` | `TIMESTAMP` | SoftDeletes |

**Indexes:**
- `PRIMARY KEY (organizer_id)`
- `UNIQUE INDEX idx_organizer_slug (slug)`
- `INDEX idx_organizer_active (is_active)`
- `INDEX idx_organizer_creator (created_by_user_id)`

#### Table: `organizer_user`

| Column | Type | Constraints |
|--------|------|-------------|
| `organizer_user_id` | `BIGINT UNSIGNED PK` | Auto-increment |
| `organizer_id` | `BIGINT UNSIGNED FK → organizer.organizer_id` | CASCADE on delete |
| `user_id` | `BIGINT UNSIGNED FK → users.id` | CASCADE on delete |
| `role` | `VARCHAR(50)` | NOT NULL (admin, editor, viewer) |
| `invited_by_user_id` | `BIGINT UNSIGNED FK → users.id` | nullable, SET NULL |
| `joined_at` | `TIMESTAMP` | nullable (null = pending invite) |
| `created_at` / `updated_at` | `TIMESTAMP` | — |

**Indexes:**
- `PRIMARY KEY (organizer_user_id)`
- `UNIQUE INDEX idx_organizer_user_unique (organizer_id, user_id)`
- `INDEX idx_organizer_user_user (user_id)`
- `INDEX idx_organizer_user_role (organizer_id, role)`

**Note:** No SoftDeletes on pivot — membership is either present or removed. Audit trail covers additions/removals via Activitylog.

---

### Key Business Rules

1. **Organizer Creation**
   - Any authenticated user MAY create an organizer (for MVP simplicity).
   - The creator automatically becomes the first `admin` of that organizer.
   - `super_admin` and `platform_admin` can create organizers on behalf of others and manage all organizers.

2. **Team Member Management**
   - Only `organizer_admin` (pivot role) can invite/remove members.
   - Invitations are by email. If the email belongs to an existing user, they are linked immediately; otherwise, a placeholder logic is deferred (MVP: require user to exist or register first).
   - A user can belong to multiple organizers with different roles.
   - Removing a member deletes the `organizer_user` row (no soft delete).
   - An organizer MUST have at least one `admin` at all times (business constraint, enforce in Action).

3. **Role Hierarchy Within Organizer**
   - `admin`: Full CRUD on organizer settings and team management.
   - `editor`: Can create/edit events and products; cannot manage team or organizer settings.
   - `viewer`: Read-only access to events and metrics.

4. **Permissions per Role (Organizer-scoped)**
   - These are **pivot-level**, not Spatie permissions (avoids exploding permission matrix across N organizers).
   - Use a simple `enum` or string check on the pivot, combined with Policy gates.
   - Example gate: `OrganizerPolicy::updateTeam($user, $organizer)` checks `$user->organizerMembership($organizer)?->role === 'admin'`.

5. **Global vs Organizer-scoped Authorization**
   - `super_admin`, `platform_admin`: Global powers — can CRUD any organizer, impersonate, etc.
   - `organizer_admin`, `organizer_editor`, `organizer_viewer` (global Spatie roles): These exist from Sprint 1.1 but are **global** labels. For Sprint 1.2, the actual enforcement should use the **pivot role** on `organizer_user`.
   - **Decision needed:** Do we keep global `organizer_*` roles as a fallback/default, or deprecate them in favor of pivot-only roles?

---

### UI/UX Considerations

#### Screens Needed

1. **Organizer CRUD (for super_admin / platform_admin)**
   - Route: `/admin/organizers`
   - List all organizers (table with name, slug, status, created_at).
   - Create/edit form (name, slug, description, branding, contact).
   - Deactivate/activate (soft delete).

2. **Organizer Dashboard (for team members)**
   - Route: `/organizer/{slug}/dashboard`
   - KPI cards: active events, total sales, team members.
   - Recent activity feed (from Activitylog, scoped to organizer).

3. **Organizer Settings (for admin)**
   - Route: `/organizer/{slug}/settings`
   - Edit name, slug, description, logo, colors, contact info.

4. **Team Management (for admin)**
   - Route: `/organizer/{slug}/team`
   - List members with roles.
   - Add member by email + role selector.
   - Remove member / change role.
   - Prevent removing last admin.

#### Layout Decisions

- **Admin panel** (`/admin/*`): For `super_admin` and `platform_admin` to manage all organizers globally. Uses existing `layouts.app`.
- **Organizer panel** (`/organizer/{slug}/*`): For team members. Could reuse `layouts.app` with organizer-scoped sidebar, OR create `layouts.organizer` if navigation diverges significantly.
- **MVP approach:** Reuse `layouts.app` and conditionally show organizer-specific sidebar items based on current context. Create `layouts.organizer` only if Sprint 1.4 demands it.

#### Components to Reuse/Create

- Reuse: `components/ui/button`, `components/form/field`, `components/form/password-input`.
- Create (if not existing):
  - `components/form/select.blade.php` (role selector)
  - `components/ui/card.blade.php` (KPIs)
  - `components/ui/table.blade.php` (team list, organizer list)

---

### Integration Approach

#### Spatie Permission Integration

- **Current state:** Spatie roles are global (`super_admin`, `platform_admin`, etc.).
- **Sprint 1.2 approach:** Do NOT use Spatie permissions for organizer-scoped access. Use the `organizer_user.role` pivot column.
- **Why:** Spatie Permission does not natively support multi-tenant/organization-scoped permissions without extensions. Adding a custom pivot is simpler and sufficient for Fase 1-3.
- **Future (Fase 4):** If multi-tenant SaaS requires granular per-organizer permissions, evaluate `spatie/laravel-permission` with a `team` feature or a custom package. Not needed now.

#### Activity Logging

- `Organizer` model MUST use `LogsActivity` trait, logging: `name`, `slug`, `is_active` (not sensitive fields).
- `OrganizerUser` pivot: Since pivots are not standard Eloquent models, use explicit Activity logging in Actions:
  - `RecordAuthActivityAction` pattern can be generalized or replicated:
    - `team.member.added`
    - `team.member.removed`
    - `team.member.role_changed`
- Use `subject: $organizer`, `causer: $user`.

#### Auth Integration

- New routes under `Route::middleware(['auth'])`.
- Use custom middleware `EnsureOrganizerAccess` (or Policy-based) to check if the authenticated user belongs to the requested organizer.
- For `/admin/organizers`, use `role:super_admin|platform_admin` middleware.

#### Route Structure

```php
Route::middleware(['auth'])->group(function () {
    // Global admin
    Route::middleware(['role:super_admin|platform_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/organizers', [AdminOrganizerController::class, 'index'])->name('organizers.index');
            // ... CRUD
        });

    // Organizer-scoped
    Route::prefix('organizer/{organizer:slug}')
        ->name('organizer.')
        ->middleware(['can:view,organizer'])
        ->group(function () {
            Route::get('/dashboard', [OrganizerDashboardController::class, '__invoke'])->name('dashboard');
            Route::get('/settings', [OrganizerSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [OrganizerSettingsController::class, 'update'])->name('settings.update');
            Route::get('/team', [OrganizerTeamController::class, 'index'])->name('team.index');
            Route::post('/team', [OrganizerTeamController::class, 'store'])->name('team.store');
            Route::delete('/team/{user}', [OrganizerTeamController::class, 'destroy'])->name('team.destroy');
        });
});
```

---

### Test Strategy

#### Unit Tests

- `OrganizerPolicyTest`: Each gate for each role combination.
- `OrganizerUserTest`: Pivot constraints, uniqueness, role enum validation.

#### Feature Tests

- `CreateOrganizerTest`: Valid data, slug uniqueness, creator becomes admin, activity logged.
- `UpdateOrganizerTest`: Only admin/platform_admin can update; fields actually change.
- `DeleteOrganizerTest`: Soft delete works; related events not cascade-deleted (future constraint).
- `OrganizerTeamManagementTest`: Add member, remove member, change role, prevent last-admin removal, unauthorized access.
- `OrganizerDashboardTest`: Accessible to team members; shows correct data.

#### Authorization Tests

- `OrganizerAuthorizationTest`: Cross-organizer access denied (user A in Org 1 cannot access Org 2).
- `GlobalAdminOrganizerTest`: `super_admin` can access any organizer; `attendee` cannot.

---

### Risks and Open Questions

| Risk | Impact | Mitigation |
|------|--------|------------|
| Confusion between global `organizer_*` Spatie roles and pivot roles | High | Document clearly; consider removing global `organizer_*` roles and keeping only `super_admin`, `platform_admin`, `attendee` globally. |
| Inviting non-existent users | Medium | MVP: only allow inviting registered users by email. Defer "invite by email + registration link" to later sprint. |
| Last admin removal | Medium | Enforce in `RemoveTeamMemberAction`: count admins before delete. |
| Slug collisions | Low | Validate unique; auto-generate from name if blank. |
| Route/model binding with slug | Low | Use `{organizer:slug}` in routes; ensure slug is URL-safe. |

#### Open Questions

1. **Should global Spatie roles `organizer_admin`, `organizer_editor`, `organizer_viewer` be removed?**
   - They were seeded in Sprint 1.1 but are not functional yet.
   - If we keep them, they could act as a "default role when joining any organizer" — but that adds complexity.
   - **Recommendation:** Deprecate global `organizer_*` roles. Use pivot roles exclusively. Update `RoleSeeder` comment to note this.

2. **Who can initially create an organizer?**
   - Option A: Any authenticated user (simplest, fits MVP).
   - Option B: Only users with a global role (e.g., `platform_admin` creates them).
   - **Recommendation:** Option A for MVP. Add a config flag or gate if the requirement changes.

3. **Do we need an `OrganizerContext` middleware now?**
   - Sprint 1.4 mentions it explicitly.
   - For Sprint 1.2, route-model binding and policies are sufficient.
   - **Recommendation:** Defer `OrganizerContext` middleware to Sprint 1.4 unless team management screens need it.

4. **Should `organizer_user` have an ID?**
   - Following boilerplate conventions (PK as `{model}_id`), yes: `organizer_user_id`.
   - This also makes Activitylog references cleaner.

---

### Recommended Next Steps

1. **sdd-propose**: Create the change proposal with scope (organizer CRUD + team management), rollback plan, and PR slicing strategy.
2. **sdd-spec**: Write delta specs for:
   - Organizer CRUD (global admin)
   - Organizer team management
   - Organizer dashboard/settings
3. **sdd-design**: Design the Organizer model, pivot, policies, routes, and Volt components.
4. **sdd-tasks**: Break into implementation tasks (migrations, models, actions, requests, controllers, Volt components, tests).

**No further exploration needed.** The domain is well-understood from existing documentation, and the architecture patterns from Sprint 1.1 provide a clear blueprint.
