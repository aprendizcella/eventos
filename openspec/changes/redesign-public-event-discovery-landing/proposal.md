# Proposal: Redesign Public Event Discovery Landing

## Intent

Make the public experience easier to navigate without changing `/` from its discovery-first catalog role. Visitors need clear paths to discover events, understand verified platform capabilities, access Docs, and enter the appropriate authenticated area.

## Scope

### In Scope
- Add responsive public navigation: brand, Discover Events, Features, Docs, external GitHub, theme control, and Login or Dashboard by authentication state.
- Add an accessible mobile menu, basic footer, and grounded attendee/organizer Features sections after the existing catalog.
- Add a public Docs index with introduction and cards for Getting Started, Help Center workflows, and Technical Reference.
- Preserve catalog search, filters, URL state, tenant scope, visibility rules, pagination, and `/`.

### Out of Scope
- Marketplace positioning, native apps, unsupported integrations, or unverified feature claims.
- Detailed Docs guides, category content, or an external documentation platform.
- Moving discovery to another route or redesigning catalog behavior.

## Capabilities

### New Capabilities
- `public-site-navigation`: Accessible desktop/mobile public navigation, authenticated actions, footer, and feature-navigation content.
- `public-docs-index`: Public Docs landing page with progressive-disclosure categories rooted in verified capabilities.

### Modified Capabilities
- `public-catalog`: The root catalog MUST remain discovery-first while supporting navigational access to anchored Features content.

## Approach

Extend the existing public layout and catalog view, reusing the persisted light/dark/system control and Tailwind v4 conventions. Keep search and filters as the first substantive home interaction; link Features to concise anchored sections separated by attendee and organizer needs. Add a named internal Docs route and use the confirmed repository URL, `https://github.com/aprendizcella/eventos`, only for the external GitHub link. Use semantic controls, keyboard operation, focus management, and dark-mode parity for mobile navigation.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/layouts/public.blade.php` | Modified | Navigation, mobile menu, footer |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Modified | Anchored Features after catalog |
| `routes/web.php` | Modified | Public Docs route |
| `resources/views/public/` | New | Docs index view |
| `tests/Feature/Catalog/*`, `tests/Feature/AdminLayoutTest.php` | Modified | Catalog and public-layout regressions |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Catalog regression or diluted discovery | Medium | Preserve route/component contract; extend focused tests |
| Inaccessible mobile navigation | Medium | Test keyboard, state, and responsive rendering |
| Unsupported marketing claims | Low | Derive copy only from verified capabilities |

## Rollback Plan

Revert this change’s implementation commit(s); `/` immediately returns to its current catalog layout and the added Docs route disappears without data migration.

## Dependencies

- None; reuse existing Laravel, Livewire, Alpine, and Tailwind v4 capabilities.

## Success Criteria

- [ ] `/` retains its existing public catalog and discovery contract for root and organizer domains.
- [ ] Public navigation and footer work across viewports, themes, keyboard navigation, and guest/authenticated states.
- [ ] Docs provides a usable public index without implying deferred guides already exist.
- [ ] Features copy accurately represents attendee and organizer capabilities only.
