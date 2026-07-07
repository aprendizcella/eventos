# Design: Sprint T0 — Multitenancy Foundation

## Technical Approach

Use `spatie/laravel-multitenancy` in single-database mode. `Organizer` becomes the tenant model, and a custom tenant finder resolves the tenant by the host configured as the application's root URL (`APP_URL`) first. For the internal organizer panel, the finder may fall back to the route organizer when the request is clearly in the organizer namespace. The root domain of each environment remains without a tenant for superadmin access. Session must not be a source of tenant truth.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Tenancy model | Single DB + `organizer_id` scope | Multi-DB per tenant | Matches the current architecture and avoids an operational rewrite. |
| Tenant model | Reuse `Organizer` as the tenant | Create separate `Tenant` model | Keeps one source of truth for branding, domain, and scope. |
| Tenant resolution | Root-URL host-first with route fallback for internal organizer URLs; global routes remain tenant-less | Host-only or route-only | Preserves custom domains, keeps the current panel working, and protects the superadmin global context. |
| Async context | Tenant-aware jobs by default | Manual tenant passing everywhere | Lower error rate and safer for notifications/billing later. |

## Data Flow

`Request host` → custom tenant finder → current tenant organizer → scoped queries

`Organizer route` → tenant finder fallback → current tenant organizer → scoped queries

`Global admin / root domain` → no tenant current → organizer list / switcher / global screens

`Dispatch job/listener` → tenant-aware payload → queue worker restores organizer context → action runs with correct tenant

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add multitenancy package. |
| `config/multitenancy.php` | Create | Package config for single DB mode. |
| `app/Models/Organizer.php` | Modify | Implement tenant contract/trait. |
| `app/Support/Multitenancy/OrganizerTenantFinder.php` | Create | Custom host-first tenant resolver with fallback. |
| `bootstrap/app.php` | Modify | Register package hooks / middleware. |
| `routes/web.php` / `routes/api.php` | Modify | Keep organizer-aware routes compatible with tenant context. |
| `app/Listeners/*` / `app/Jobs/*` | Modify | Mark async work as tenant-aware where needed. |
| `tests/Feature/Organizers/*` | Modify/New | Verify tenant resolution and isolation. |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Finder precedence, tenant contract wiring | Small focused tests. |
| Integration | Host-based resolution, route fallback, tenant-aware queue restore | Feature tests with real request/session context. |
| E2E | Organizer isolation across domains | Browser/feature smoke covering domain-specific URLs. |

## Migration / Rollout

No data split required. Roll out in one slice with config + middleware first, then async context, then tests. Keep `organizer.detect` active until the new finder passes parity tests, but do not let it override a resolved host tenant.

## Open Questions

- [ ] Exact route pattern that is allowed to fall back when host resolution is absent.
