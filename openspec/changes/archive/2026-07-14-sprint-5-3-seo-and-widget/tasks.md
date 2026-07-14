# Tasks: Sprint 5.3 — SEO & Public Widget

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 260-380 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single delivery slice on main |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Lock public route, redirect, SEO hook, and sitemap contract | Main | Includes tests first; admin routes/forms/behavior untouched |
| 2 | Add widget endpoint, JSON contract, and embed loader | Main | Keep scoped to public API/widget paths only |

## Phase 1: Route & Contract Foundation

- [x] 1.1 Write failing feature tests for canonical slug detail, numeric 301 redirect, and hidden-event non-disclosure in `tests/Feature/Catalog/PublicEventDetailTest.php` and `tests/Feature/Public/EventRedirectTest.php`.
- [x] 1.2 Write failing sitemap tests for `/sitemap.xml` XML output, inclusion rules, and empty-set behavior in `tests/Feature/Public/SitemapTest.php`.
- [x] 1.3 Write failing widget contract tests for organizer lookup, limit validation, and CORS in `tests/Feature/Public/WidgetTest.php`.

## Phase 2: Public Routing & Presentation

- [x] 2.1 Update `routes/web.php` so the public detail route resolves `{event:slug}` and legacy numeric `/events/{id}` issues a 301 redirect.
- [x] 2.2 Add `app/Http/Controllers/Public/EventRedirectController.php` to resolve by primary key, enforce public/published visibility, and redirect to the canonical slug URL.
- [x] 2.3 Add `app/ViewModels/Public/EventSeoViewModel.php` plus `resources/views/components/seo/head.blade.php` and `@stack('seo')` in `resources/views/layouts/public.blade.php`.
- [x] 2.4 Update `resources/views/livewire/public/events/event-detail-public.blade.php` to push SEO metadata and use slug-based canonical links only.

## Phase 3: Sitemap & Widget Delivery

- [x] 3.1 Add `app/Http/Controllers/Public/SitemapController.php` and `resources/views/sitemap/index.blade.php` to emit public XML sitemap entries with canonical slug URLs.
- [x] 3.2 Add `routes/api.php`, `app/Http/Controllers/Public/EventWidgetController.php`, and the widget JSON response contract for organizer-scoped published events.
- [x] 3.3 Add `public/js/widget.js` for the external embed loader with scoped markup and configurable limit handling.
- [x] 3.4 Add or adjust `config/cors.php` so only the widget API path is cross-origin enabled; admin routes/forms/behavior remain untouched.

## Phase 4: GREEN / REFACTOR

- [x] 4.1 Run the focused feature tests, fix failing route/controller/view issues, and verify SEO tags, redirect behavior, sitemap XML, and widget payload shape.
- [x] 4.2 Refactor shared public-event visibility/URL helpers in `app/Models/Event.php` only if needed, keeping existing admin behavior unchanged.
- [x] 4.3 Run the narrow test suite again and confirm no hidden-event metadata leaks and no admin route regressions.
