# Proposal: Sprint 6.1 — Backoffice de Plataforma

## Intent

Provide platform admins a dedicated backoffice for user management, event moderation, and global settings. Enforce `super_admin` vs `platform_admin` isolation — only `super_admin` grants global roles.

## Scope

### In Scope
- Admin layout, dashboard, user mgmt, event moderation, platform settings, admin API
- Work units: (1) auth foundation (global roles, Spatie team 0 isolation), (2) admin dashboard, (3) user management, (4) reversible event moderation, (5) platform settings/commission fallback, (6) admin API

### Out of Scope
- GDPR deletion/anonymization, billing/invoicing admin UI, dedicated audit log viewer, automated refund/payout changes on suspension, organizer-level admin tools

## Capabilities

### New Capabilities
- `admin-authorization`: Global role guards (`role:super_admin|platform_admin`) with explicit `setPermissionsTeamId(0)` for global context; no ambient tenant leakage
- `admin-user-management`: List/view/edit/suspend/restore users; send password reset; assign/revoke global roles (`super_admin` only); protect final active `super_admin` from suspension/deletion
- `admin-event-moderation`: List all events across organizers; suspend (with previous status save + mandatory reason + actor) and restore; suspended excluded from catalog/search; no automatic refund/payout changes
- `platform-settings`: Singleton `PlatformSetting` model, JSON-schema validated `settings` column, concurrency-safe (lock/version), activity-logged; commission = fallback defaults only; organizer values override; changes affect future payouts only; historical immutable

### Modified Capabilities
- `event-authorization`: Add `super_admin` (global role grant) and `platform_admin` (organizer-scoped role mgmt only) distinction
- `event-lifecycle`: Add `suspended` status; store `previous_status` (draft/published/paused) on suspend; restore transitions back to previous; suspended excluded from public catalog, search index, and organizer dashboards
- `commission-tracking`: Fallback chain: organizer billing settings → platform settings → hardcoded default; historical payouts immutable on platform config change
- `public-catalog` / `event-search`: Filter out `suspended` events

## Approach

Spatie `setPermissionsTeamId(0)` at auth middleware for global roles. Volt components in `livewire/admin/`. Thin API controllers (`/api/v1/admin/*`) + Actions, Sanctum-auth, rate-limited, paginated, versioned, consistent JSON error envelope. DB lock for platform settings writes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Models/PlatformSetting.php` | New | Singleton with JSON column, optimistic lock |
| `app/Enums/EventStatus.php` | Modified | Add `Suspended` case |
| `config/permission.php` | Modified | Team resolver for global context |
| `routes/api.php` | Modified | Add `api/v1/admin/*` group |
| `routes/web.php` | Modified | Add admin Volt routes |
| `resources/views/livewire/admin/` | New | Volt components |
| `app/Http/Controllers/Api/V1/Admin/` | New | Thin, bounded controllers |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Spatie team id not set before permission check | High | Bootstrap middleware sets `team_id: 0` on admin auth and tests both tenant and global contexts |
| Final admin self-lockout | Low | Protect last `super_admin` in action logic |
| Platform vs organizer commission confusion | Med | Explicit fallback chain: organizer → platform → hardcoded |

## Rollback Plan

Revert migrations, remove Volt components + controllers, restore route files, remove `suspended` from EventStatus enum and revert public catalog/event-search queries.

## Dependencies

- Spatie `laravel-permission` (teams enabled, `Organizer` as team model)
- Sanctum (already installed)

## Success Criteria

- [ ] `platform_admin` cannot assign/revoke global roles (403)
- [ ] Last active `super_admin` cannot be suspended
- [ ] Suspended event hides from catalog/search; restore returns to prior status
- [ ] Platform commission fallback applies only when organizer settings absent
- [ ] Historical payouts unchanged after platform commission update
- [ ] All admin API endpoints tested with Pest (Feature tests, role context, Spatie team 0)
