## Exploration: Redesign Public Event Discovery Landing

### Current State

`/` is already the public, tenant-aware **Discover Events** catalog. It provides full-text search, category/city/from-date filters, URL-synced state, result summaries, loading states, pagination, and public/published-only visibility. The public layout is a compact sticky header with brand, either Login or Dashboard, and the reusable light/dark/system theme control; it has no public navigation links, GitHub link, Features content, or Docs route.

The application is a self-hosted, tenant-aware ticketing platform, not a marketplace. The root domain shows eligible events across organizers, while an organizer domain scopes the catalog to that organizer. The product already supports event and venue management, ticket products/pricing/promotions, checkout and Stripe payments, QR/PDF tickets, attendee orders, check-in, waitlists, bulk attendee messages, exports, organizer reports, SEO/sitemaps, embeddable event widgets, and the current public catalog/detail experience.

External references confirm that Hi.Events is organizer-oriented, Eventbrite combines attendee discovery with organizer tools, and Ticketea is a defunct historical marketplace brand absorbed into Eventbrite. Reuse only durable interaction patterns: a clear top-level navigation, discoverability-oriented search/filter hierarchy, and a deliberate separation between attendee discovery and organizer capabilities. Do not reuse their branding, copy, or marketplace positioning.

### Affected Areas

- `resources/views/layouts/public.blade.php` — public header/footer and the natural integration point for desktop/mobile navigation, GitHub, authenticated Dashboard access, and the existing theme toggle.
- `resources/views/livewire/public/events/event-list-public.blade.php` — root catalog/home content; would host the Features anchors/sections while preserving discovery as the primary page purpose.
- `resources/views/components/catalog/*.blade.php` — existing catalog primitives that establish the card, search, filter, loading, result-summary, and dark-mode conventions to preserve.
- `resources/views/components/ui/theme-toggle.blade.php` and `resources/views/components/ui/theme-init.blade.php` — reusable persisted light/dark/system behavior; do not duplicate theme state.
- `routes/web.php` — contains the catalog, login, and dashboard routes; no Docs destination currently exists.
- `tests/Feature/Catalog/PublicCatalogTest.php`, `tests/Feature/Catalog/PublicCatalogFilterTest.php`, `tests/Feature/PublicCatalogSearchTest.php` — regression coverage for the catalog as home; extend rather than replace.
- `tests/Feature/AdminLayoutTest.php` — existing layout/theme-toggle contract pattern to mirror for public-navigation rendering.
- `resources/css/app.css` — Tailwind CSS v4 CSS-first setup and explicit class-based dark-mode variant; no separate Tailwind config exists.

### Approaches

1. **Discovery-first home with anchored capability sections** — retain `/` as the catalog; add a responsive public navigation (`Discover Events`, `Features`, `Docs`, GitHub) and place concise, evidence-based Features sections after the discovery results. Separate attendee-facing discovery/ticketing capabilities from organizer-facing operational capabilities within the sections.
   - Pros: preserves the established home contract and its tests; makes event discovery immediately useful; avoids claiming marketplace/network effects; uses the existing public layout and components.
   - Cons: the root page becomes longer; the Docs link needs a destination decision before it can be functional.
   - Effort: Medium.

2. **Separate product-marketing landing before the catalog** — make `/` a marketing page with a hero, split attendee/organizer routes, and move discovery to a new catalog route.
   - Pros: strongest space for product storytelling and distinct audience journeys.
   - Cons: contradicts the requirement that Discover Events remain home; requires route, SEO, navigation, and test-contract changes; risks presenting a self-hosted platform as an Eventbrite-style marketplace.
   - Effort: High.

### Recommendation

Choose **Discovery-first home with anchored capability sections**. Keep the existing `public.events.catalog` route at `/`, with search and filters as the first substantive interaction. Add a responsive, Hi.Events-inspired functional navigation without copying its wording or visuals: brand and `Discover Events` on the left; `Features`, `Docs`, and an external GitHub link in the public-nav group; Login for guests or Dashboard for authenticated users plus the existing theme control on the right. On small screens, the same destinations require an accessible menu rather than hidden or duplicated links.

Features must describe only verified functionality and should be grouped by audience to avoid a muddled landing: **For attendees** (discover/filter/search events, event details, calendar actions, checkout, tickets/orders) and **For organizers** (manage events/venues, ticket pricing and promotions, attendee operations including QR check-in/waitlists/messaging/export, reporting, SEO and embeds). The wording must not imply an Eventbrite-style audience marketplace, social discovery, native mobile apps, integrations, or documentation that does not exist.

The repository URL is `https://github.com/aprendizcella/eventos`. The Docs navigation item is currently a product-navigation requirement, not an implemented capability: agree its stable target before implementation. A minimal public Docs placeholder route is feasible, but its deferred content should be explicitly out of scope for this redesign; linking to repository documentation instead is lower effort but conflates product docs with source-code documentation.

### Risks

- A new marketing-first root would regress the established catalog route, tenant scoping, SEO, and discovery tests; keep `/` unchanged as the catalog.
- A Docs link without an agreed target produces a dead-end navigation item; decide the route/URL and placeholder behavior in the proposal.
- Features copy can overstate the product if it blurs implemented self-hosted organizer tooling with marketplace capabilities; derive every claim from existing routes, components, and product behavior.
- Public navigation must remain responsive, keyboard-accessible, and fully dark-mode compatible; reuse the existing Alpine/theme and Tailwind v4 conventions.
- The existing public layout contains only a minimal header/footer, so the redesign may exceed the catalog's current focused test coverage unless navigation and feature-section contracts are added.

### Ready for Proposal

Yes — proceed with `sdd-propose` after recording the Docs target decision (public placeholder route versus repository documentation). The proposal should preserve `/` and `public.events.catalog`, define the public-navigation accessibility contract, enumerate only verified feature claims, and forecast a direct-to-main implementation under the provided 5,000-line review budget.
