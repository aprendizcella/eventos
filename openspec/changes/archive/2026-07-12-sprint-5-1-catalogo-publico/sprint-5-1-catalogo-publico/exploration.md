# Exploration: Sprint 5.1 Public Catalog

### Current State

- Phase 5 is only documented at roadmap level today.
- No formal `openspec/changes/sprint-5-1-*` folder existed before this work.
- The application already has a public layout, checkout, waitlist, and tenant detection by host.
- The main domain resolved from `config('app.url')` acts as the tenant-less global context.
- Organizer custom domains resolve to the matching tenant.

### Affected Areas

- `docs/01-producto/PLAN_IMPLEMENTACION.md` - phase plan source of truth.
- `docs/00-estado/ESTADO_EJECUCION.md` - execution status and next step.
- `docs/03-ux-ui/REFERENCIAS_UX.md` - UX inspiration for the public catalog.
- `resources/views/layouts/public.blade.php` - public shell already exists.
- `routes/web.php` - root route currently still points to `welcome`.
- `resources/views/livewire/public/` - existing public checkout/waitlist/order flows.
- `config/multitenancy.php` and `app/Support/Multitenancy/OrganizerTenantFinder.php` - host-based tenant resolution.

### Approaches

1. **Global root + tenant-scoped organizer catalog** - same routes, different scope depending on host.
   - Pros: matches the clarified product rule, keeps one public surface.
   - Cons: query logic must be explicit to avoid tenant leakage.
   - Effort: Medium.

2. **Separate global and tenant routes** - distinct URLs for platform and organizer catalogs.
   - Pros: simple to reason about in routing.
   - Cons: duplicates UX and breaks the shared public discovery model.
   - Effort: Medium-High.

### Recommendation

Use the same public catalog routes and resolve scope by current host/tenant context. This aligns with the current multitenancy model and keeps the public experience consistent.

### Risks

- Accidentally exposing non-public events in the global catalog.
- Confusing root-domain discovery with organizer-domain discovery if the scope is not made explicit in the queries.
- Over-expanding Sprint 5.1 into search/SEO/widget work that belongs to later sprints.

### Ready for Proposal

Yes.
