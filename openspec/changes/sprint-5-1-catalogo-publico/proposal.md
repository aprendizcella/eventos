# Proposal: Sprint 5.1 - Public Catalog

## Intent

Build the first public discovery surface for Eventos: a public catalog and event detail experience that shows all published events on the root domain and only tenant events on organizer domains.

## Scope

### In Scope
- Replace the root `welcome` page with the public catalog.
- Create a public event list with filters by category, city, and date.
- Create a public event detail page accessible without authentication.
- Create a reusable event card for catalog and future discovery surfaces.
- Reuse the existing checkout flow from the public detail page.
- Keep tenant scoping aligned with the existing host-based multitenancy model.

### Out of Scope
- Full-text search with Scout/Meilisearch.
- SEO metadata, sitemap, and slug strategy.
- Embeddable widget and external API surface.
- Performance/caching/CDN work.
- Copying the HI.EVENTS UI literally.

## Capabilities

### New Capabilities
- `public-event-discovery`: browse public events from root or tenant domains.
- `public-event-detail`: inspect an event without authentication.

### Modified Capabilities
- `tenant-aware-public-routing`: public pages now resolve scope by host.
- `checkout-entrypoint`: event detail becomes the public entry point to checkout.

## Approach

Use the existing `layouts.public` shell and Livewire Volt components under `resources/views/livewire/public/events/`. Scope event queries by current tenant context: the root domain returns all public/published events, while organizer domains return only the current tenant's public/published events. Keep filters server-driven and simple for Sprint 5.1.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `routes/web.php` | Modified | Root route and public event detail routing. |
| `resources/views/layouts/public.blade.php` | Modified | Public shell may need catalog-friendly header/body treatment. |
| `resources/views/livewire/public/events/` | New | Catalog, detail, and reusable card components. |
| `docs/01-producto/PLAN_IMPLEMENTACION.md` | Modified | Phase 5 scope clarification. |
| `docs/00-estado/ESTADO_EJECUCION.md` | Modified | Next-step alignment. |
| `docs/03-ux-ui/REFERENCIAS_UX.md` | Modified | HI.EVENTS discovery UX references. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Tenant leakage in global catalog | Medium | Scope all public queries explicitly by tenant context and visibility. |
| Scope creep into later discovery work | Medium | Freeze Sprint 5.1 to list/detail/card/filters/CTA only. |
| Public UX drifting away from the rest of the app | Low | Reuse existing Tailwind tokens and the current public layout shell. |

## Rollback Plan

Revert the public catalog routes and components, restoring the current welcome page behavior and keeping checkout/waitlist flows intact.

## Success Criteria

- [ ] Root domain shows all public published events across organizers.
- [ ] Organizer domains show only their own public published events.
- [ ] Filters by category, city, and date work.
- [ ] Event detail is public and links to checkout.
- [ ] Existing public checkout and waitlist flows keep working.
