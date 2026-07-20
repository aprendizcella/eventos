# Design: Sprint 6.1 — Backoffice de Plataforma

## Technical Approach

Six work units building on existing architecture: Spatie teams (Organizer as team), Volt components in `livewire/admin/`, thin API controllers under `Api/V1/Admin/`, Actions with DTOs, and Pest feature tests. Global admin context enforced via middleware that calls `setPermissionsTeamId(0)` with restoration guard.

## Architecture Decisions

### Decision: Global Admin Context Middleware

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Set team 0 once in middleware | Simple, single responsibility | ✅ **Create `EnsureGlobalAdminContext` middleware** — calls `setPermissionsTeamId(0)` before handler, captures previous team_id, restores it in the terminate phase. Prevents tenant leakage into subsequent requests without manual reset at every policy/action. |
| Bypass teams entirely | Would break organizer-scoped role resolution | ❌ Rejected — teams (`organizer_id`) are required for organizer role isolation |
| Manual setPermissionsTeamId at every call site | Error-prone, easy to forget | ❌ Rejected — middleware is the correct Laravel seam |

### Decision: Authorization Matrix

| Role | Global role mgmt | Organizer-scoped role mgmt | Read all data |
|------|-----------------|---------------------------|---------------|
| `super_admin` | ✅ Grant/revoke | ✅ | ✅ |
| `platform_admin` | ❌ 403 | ✅ via existing team mgmt | ✅ |

Web routes: existing `role:super_admin|platform_admin` middleware in `admin` prefix. API routes: Sanctum auth → `EnsureGlobalAdminContext` → role check in controller/action.

### Decision: User Suspension Storage

Add `suspended_at` nullable timestamp to `users` table. On suspend: set `suspended_at`, delete current Sanctum tokens, invalidate session. On restore: clear `suspended_at`. No GDPR deletion (out of scope — spec Requirement: GDPR Deletion Deferred). The User model already logs activity via `LogsActivity` trait.

### Decision: Reversible Event Suspension

Add `previous_status` (nullable string) and `suspended_at` to `event` table. New `SuspendEventAction` stores prior status, sets status to `Suspended`, requires `reason` string, records `suspended_by` actor via activity log. `RestoreEventAction` transitions back to stored `previous_status`, clears fields. Add `Suspended` case to `EventStatus` enum. Update `shouldBeSearchable()` to exclude `Suspended`. No refund/payout automation.

### Decision: Platform Settings Singleton

| Option | Tradeoff | Decision |
|--------|----------|----------|
| JSON column + lock_version | Matches existing organizer/event settings pattern; app-layer validation; optimistic locking via version check | ✅ **Create `PlatformSetting` model** with `settings` JSON column and `lock_version` integer. Validation via FormRequest. Activity-logged. |
| Typed columns per setting | Schema changes for every new setting | ❌ Rejected — too rigid for evolving platform config |

Commission fallback is resolved inside `CreatePayoutAction::resolveBillingSettings()`: organizer billing settings → `PlatformSetting::commission()` → `config('tickets.commission_default')`. Nullable commission fields: `null` = no override; `0` = explicit zero (uses strict `!== null` check). Future-only: commission snapshots at payout creation are immutable after generation.

### Decision: Admin Layout

New `layouts/admin.blade.php` extending `app.blade.php`, overriding sidebar slot with admin nav. Volt components in `resources/views/livewire/admin/{dashboard,users,events,settings}.blade.php`.

## Data Flow

```
User → /admin/* or /api/v1/admin/*
  → EnsureGlobalAdminContext (team_id=0)
  → role:super_admin|platform_admin (web) or Sanctum + role check (api)
  → Controller (thin) → Action (logic + audit)
  → Resource/ViewModel → JSON/Volt response
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Enums/EventStatus.php` | Modify | Add `Suspended` case |
| `app/Http/Middleware/EnsureGlobalAdminContext.php` | Create | `setPermissionsTeamId(0)` with restore |
| `app/Models/PlatformSetting.php` | Create | Singleton, JSON settings, lock_version, LogsActivity |
| `app/Models/User.php` | Modify | Add `suspended_at` to casts, scope `active`, `isSuspended()` helper |
| `app/Models/Event.php` | Modify | Add `previous_status`, `suspended_at` to casts; update `shouldBeSearchable()` |
| `app/Actions/Events/SuspendEventAction.php` | Create | Store previous status, set suspended, mandatory reason, activity log |
| `app/Actions/Events/RestoreEventAction.php` | Create | Restore to previous_status, activity log |
| `app/Actions/Admin/Users/SuspendUserAction.php` | Create | Set suspended_at, revoke tokens/sessions, guard last super_admin |
| `app/Actions/Admin/Users/RestoreUserAction.php` | Create | Clear suspended_at |
| `app/Actions/Admin/Users/AssignGlobalRoleAction.php` | Create | Guard platform_admin (403), assign with team_id=0 |
| `app/Actions/Admin/PlatformSettings/UpdatePlatformSettingsAction.php` | Create | Validate, optimistic lock check, activity log |
| `app/Http/Middleware/EnsureGlobalAdminContext.php` | Create | Set team 0, restore on terminate |
| `app/Http/Controllers/Api/V1/Admin/*.php` | Create | Thin controllers per resource (users, events, settings) |
| `app/Http/Requests/Api/V1/Admin/*.php` | Create | FormRequests with `toDto()` |
| `app/Http/Resources/Api/V1/Admin/*.php` | Create | Admin-specific resource responses |
| `config/permission.php` | Modify | Optionally override `team_resolver` for admin guard (or rely on middleware) |
| `routes/web.php` | Modify | Add new `admin` Volt routes under existing prefix |
| `routes/api.php` | Modify | Add `api/v1/admin/*` routes with Sanctum + rate limit |
| `resources/views/layouts/admin.blade.php` | Create | Admin layout extending app-blade |
| `resources/views/livewire/admin/*.blade.php` | Create | Volt components (dashboard, users, events, settings) |
| `database/migrations/*_create_platform_setting_table.php` | Create | Platform settings table |
| `database/migrations/*_add_suspended_to_users_table.php` | Create | suspended_at column |
| `database/migrations/*_add_suspension_columns_to_event_table.php` | Create | previous_status, suspended_at columns |
| `bootstrap/app.php` | Modify | Register `EnsureGlobalAdminContext` middleware alias |

## Interfaces / Contracts

```php
// PlatformSetting — singleton access
final class PlatformSetting extends Model
{
    public function scopeApply(Builder $query): Builder // enforces single row
    public function is(string $key): mixed
    public static function current(): ?PlatformSetting
}

// Event suspension preserves prior status
readonly class SuspendEventAction
{
    public function __invoke(Event $event, string $reason, User $actor): Event
}

// Commission fallback chain (pseudo)
CreatePayoutAction::resolveBillingSettings(Invoice $invoice): ?BillingSettings
{
    // Resolve organizer settings first, then platform fallback, before CommissionCalculator.
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | EventStatus transitions, PlatformSetting singleton, CommissionResolver chain | Pure Pest unit tests, no DB |
| Feature | Admin auth team 0 isolation, role matrix, user suspend/restore, event moderation, platform settings CRUD+lock | Feature tests with `LazilyRefreshDatabase`, `setPermissionsTeamId(0)` in beforeEach |
| Feature | API endpoints: auth, pagination, rate limits, 401/403 | Feature tests acting as Sanctum-authenticated admin |
| Feature | Suspended event exclusion from catalog and search | Feature test querying scopes and `shouldBeSearchable()` |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. All changes are within the Laravel application layer (models, controllers, middleware, Volt components).

## Migration / Rollout

New migrations are additive (new tables, nullable columns). Rollback: reverse migration order. No data migration required. Protect last `super_admin` in action logic at application layer, not in migration.

## Open Questions

None. Commission fallback is resolved at payout-generation settings resolution, and the admin layout reuses the existing `app-shell` with an admin navigation slot.
