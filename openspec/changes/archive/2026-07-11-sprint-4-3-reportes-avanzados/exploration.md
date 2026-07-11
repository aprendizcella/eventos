## Exploration: Sprint 4.3 - Reportes Avanzados

### Current State
Sprint 4.2 is implemented and archived. The repo already has read-only billing/payout reporting patterns in place, and the HI.EVENTS image set shows the exact UX language we can adapt: report overviews, metric cards, table-heavy summaries, top-right filters, CSV exports and contextual banners. No 4.3 SDD exists yet.

### Affected Areas
- `docs/00-estado/ESTADO_EJECUCION.md` - must show 4.2 closed and 4.3 next.
- `docs/01-producto/PLAN_IMPLEMENTACION.md` - must define 4.3 scope and slices.
- `docs/03-ux-ui/REFERENCIAS_UX.md` - should capture the HI.EVENTS report patterns.
- `openspec/changes/sprint-4-3-reportes-avanzados/` - needs proposal, design, tasks and specs.
- `resources/views/livewire/organizers/*` - likely organizer reporting surface.
- `app/ViewModels/*` and `app/Services/*` - shared read layer for aggregates.

### Approaches
1. **Shared report engine + two surfaces** - reuse one aggregation layer and expose organizer/admin UIs separately.
   - Pros: less duplication, clear permissions, easier to keep numeric logic consistent.
   - Cons: needs careful boundary design for shared filters and scope-specific queries.
   - Effort: Medium

2. **Organizer-first, platform second** - ship organizer reports first, then add admin/global reports later.
   - Pros: smaller slices, faster feedback.
   - Cons: duplicates some design work and delays the platform scope the user asked for.
   - Effort: Medium

3. **Single report hub with scope switcher** - one screen changes between organizer and platform views.
   - Pros: one navigation entry, shared UX.
   - Cons: higher cognitive load and more complex permission handling.
   - Effort: Medium

### Recommendation
Use a shared reporting layer with two separate surfaces, and plan the slices as shared foundation, organizer reports, then platform reports. That keeps the UI adapted to the reference, avoids a monolithic generator and still covers the admin/platform requirement.

### Risks
- Scope creep into full analytics or dashboards already covered elsewhere.
- Export formats beyond CSV may require new dependencies.
- Global admin reports can overlap with organizer reporting unless the filters and metrics are scoped precisely.

### Ready for Proposal
Yes - the scope is clear enough to open the Sprint 4.3 proposal, spec, design and tasks.
