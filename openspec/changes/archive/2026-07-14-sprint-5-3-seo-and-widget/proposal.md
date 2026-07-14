# Proposal: Sprint 5.3 — SEO & Public Widget

## Intent

Make public events discoverable by search engines and embeddable on external sites. Today the public detail route uses numeric ID binding; this sprint adds slug-based canonical URLs with 301 redirects from legacy numeric paths.

## Scope

**In**: slug as primary public URL; numeric `/events/{id}` → 301 to slug; admin routes/behavior unchanged; SEO meta (title, description, canonical, OG, Twitter Cards); XML sitemap at `/sitemap.xml`; embeddable JS widget listing organizer's published events (title, date, slug URL, configurable limit).

**Out**: embedded checkout/tickets; advanced widget filters; admin route SEO; dynamic OG images; caching/CDN; widget styling beyond scoped markup.

## Capabilities

### New
- `event-canonical-urls`: slug routing + 301 from numeric.
- `event-seo-metadata`: SEO meta, OG, Twitter Cards on public pages.
- `event-sitemap`: XML sitemap for published/public events.
- `event-embed-widget`: embeddable JS widget for organizer event listing.

### Modified
- `public-event-detail`: add SEO rendering and slug resolution.

## Approach

Route `/events/{event:slug}` + numeric 301. SEO via dedicated presenter. Sitemap via cached controller. Widget via controller returning JSON + `<script>` tag.

## Affected Areas

| Area | Impact |
|------|--------|
| `routes/web.php` | Modified — add slug route + 301 redirect |
| `app/Models/Event.php` | Modified — SEO accessors |
| `app/ViewModels/Public/EventSeoPresenter.php` | New |
| `app/Http/Controllers/Public/SitemapController.php` | New |
| `app/Http/Controllers/Public/EventWidgetController.php` | New |
| `resources/views/layouts/public.blade.php` | Modified — SEO partial in `<head>` |
| `resources/views/livewire/public/events/` | Modified — SEO partial |
| `openspec/specs/public-event-detail/spec.md` | Modified — SEO + slug requirements |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Indexed numeric URLs lose link equity | Low | 301 preserves >90% |
| Slug collision on identical titles | Low | Preserve the existing unique slug behavior |
| Widget renders inconsistently | Med | Scoped styles, no CSS leak |
| MVP widget too minimal | Med | Limit + fields in scope; advanced deferred |

## Rollback Plan

Revert routes. Remove SEO presenter and layout partials. Remove sitemap and widget controllers/routes. Slug column stays in DB (non-breaking).

## Dependencies

Event model already has `slug` + unique constraint. Sprint 5.1 detail/catalog stable. No new packages.

## Success Criteria

- [ ] `/events/{slug}` renders detail page.
- [ ] `/events/{id}` → 301 to slug.
- [ ] Detail page has `<title>`, desc, canonical, OG, Twitter Cards.
- [ ] `/sitemap.xml` valid XML with all published/public events.
- [ ] Widget renders organizer events (title, date, link).
- [ ] Widget accepts configurable limit.
- [ ] Admin routes and behavior unaffected.
