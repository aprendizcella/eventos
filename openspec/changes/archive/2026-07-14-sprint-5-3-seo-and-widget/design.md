# Design: Sprint 5.3 — SEO & Public Widget

## Technical Approach

Four capabilities deployed independently against the existing public Volt layer: slug-based canonical routing with 301 redirect, a ViewModel-driven SEO metadata partial, a plain-controller XML sitemap, and a two-part widget (JSON endpoint + external JS loader). No admin routes touched. All changes scoped to the public-facing layer.

## Architecture Decisions

### Decision: Slug routing via explicit binding

| Option | Tradeoff |
|--------|----------|
| Keep implicit `{event}` on PK | Breaks canonical URL requirement. |
| `{event:slug}` on existing Volt route + numeric `{id}` 301 | Clean. Slug is unique. Numeric redirect adds one thin Controller. |

**Choice**: Change the existing `Volt::route('/events/{event}', ...)` to `/events/{event:slug}` and add a `GET /events/{id}` route handled by `EventRedirectController` issuing HTTP 301. Volt's implicit binding naturally resolves by the `slug` column when using `{event:slug}` syntax.

### Decision: SEO metadata via ViewModel + `@stack`

**Choice**: `EventSeoViewModel` (public methods: `title`, `description`, `canonicalUrl`, `ogMeta`, `twitterMeta`). Layout gets `@stack('seo')` in `<head>`. The Volt detail component pushes the rendered partial.
**Rationale**: Matches existing `ViewModel` pattern. Zero SEO logic in the component. Layout stays clean. The ViewModel receives the Event model and `Request` for full canonical URL.

### Decision: Widget delivery — JSON endpoint + external JS loader

| Option | Tradeoff |
|--------|----------|
| Only JSON endpoint | External site owner must write their own fetch/render. Breaks "embeddable" requirement. |
| Full iframe | Heavy, loses styling control, needs separate page route. |
| JSON + <1KB JS loader | Minimal effort. Site owner pastes `<script>`. Scoped class prefix `hi-ew-*` prevents CSS leak. |

**Choice**: Public JSON endpoint `GET /api/widget/events?organizer={slug}&limit={n}` returning `{events: [{title, starts_at, url}], organizer: {name}}` (max limit 20, default 5). JS loader at `public/js/widget.js` reads `data-organizer`/`data-limit` from its own `<script>` tag, fetches endpoint, renders into a container. CORS handled via `config/cors.php` allowing `*` on that path.

### Decision: Sitemap as plain Controller

**Choice**: `SitemapController` with `__invoke` returning `response()->view('sitemap.index', ...)->header('Content-Type', 'text/xml')`. Blade view renders `<urlset>` manually. Query: `Event::published()->public()->get()`.
**Rationale**: Keep XML response outside Livewire/Volt. Simple, no dependencies, easy to test.

### Decision: CORS explicitly configured

**Choice**: Create `config/cors.php` with `allowed_origins: ['*']` only for `api/widget/*` paths. The rest stays at framework defaults.
**Rationale**: Widget must work cross-origin. Explicit config prevents accidental over-exposure.

## Data Flows

```
Slug resolution:
  Browser ──GET /events/{slug}──→ Volt route (event:slug) ──→ EventSeoViewModel ──→ layout@stack('seo')
                    ↑
  301 redirect ────┘
  GET /events/{id} ──→ EventRedirectController ──→ findOrFail ──→ redirect 301

Sitemap:
  Crawler ──GET /sitemap.xml──→ SitemapController ──→ published().public() events ──→ XML view

Widget:
  External page ──<script src="js/widget.js" data-organizer="x">──→
    JS reads data attributes ──→ fetch /api/widget/events?organizer=x&limit=5 ──→
    JSON ──→ render scoped DOM
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `routes/web.php` | Modify | Change detail route to `{event:slug}`; add `GET /events/{id}` for 301 |
| `routes/api.php` | Modify | Add `GET /widget/events` (no auth) for widget JSON |
| `config/cors.php` | Create | CORS `*` for `api/widget/*` |
| `app/Http/Controllers/Public/EventRedirectController.php` | Create | Invocable: find by PK, check visibility, 301 to slug route |
| `app/Http/Controllers/Public/SitemapController.php` | Create | Invocable: query published+public, return XML view |
| `app/Http/Controllers/Public/EventWidgetController.php` | Create | Invocable: accept `organizer` + `limit`, return JSON Resource |
| `app/ViewModels/Public/EventSeoViewModel.php` | Create | SEO metadata: title, description, canonical, OG, Twitter |
| `resources/views/components/seo/head.blade.php` | Create | Blade partial rendering `<title>`, `<meta>`, OG, Twitter tags |
| `resources/views/sitemap/index.blade.php` | Create | XML template for `urlset` |
| `public/js/widget.js` | Create | ~1KB JS: fetch JSON, render scoped `.hi-ew-*` markup |
| `resources/views/layouts/public.blade.php` | Modify | Add `@stack('seo')` in `<head>` before `@livewireStyles` |
| `resources/views/livewire/public/events/event-detail-public.blade.php` | Modify | Push SEO partial via ViewModel; use slug URL for calendar/checkout links |
| `app/Models/Event.php` | Modify | Possibly add accessor `getCanonicalUrlAttribute()` for reuse |
| `tests/Feature/Catalog/PublicEventDetailTest.php` | Modify | Add slug route tests, SEO metadata assertions |
| `tests/Feature/Public/EventRedirectTest.php` | Create | Numeric 301 + 404 scenarios |
| `tests/Feature/Public/SitemapTest.php` | Create | XML structure, inclusion rules, empty set |
| `tests/Feature/Public/WidgetTest.php` | Create | JSON shape, organizer filter, limit, CORS header |

## Interfaces / Contracts

### Widget JSON Response

```
GET /api/widget/events?organizer={slug}&limit={1-20}
Content-Type: application/json
Access-Control-Allow-Origin: *

{
  "organizer": { "name": "Acme Events" },
  "events": [
    {
      "title": "Awesome Concert",
      "starts_at": "2026-08-15T20:00:00+00:00",
      "url": "https://example.com/events/awesome-concert"
    }
  ]
}
```

Errors: 404 for unknown organizer, 422 for invalid limit, empty `events: []` for organizer with no published events.

### JS Embed Contract

```html
<div id="hi-events-widget"></div>
<script src="https://example.com/js/widget.js"
        data-organizer="acme"
        data-limit="5"></script>
```

The script finds itself via `document.currentScript`, reads `dataset.organizer` and `dataset.limit`, fetches the endpoint, and renders into the preceding element (or `#hi-events-widget`). All class names prefixed `hi-ew-*`.

## Testing Strategy

| Layer | What | How |
|-------|------|-----|
| Feature | Slug route | `get(route('public.events.detail', $event))` → 200, title visible |
| Feature | Numeric 301 | `get('/events/' . $event->event_id)` → 301 to slug |
| Feature | 404 hidden events | Private/unpublished → 404 on both slug and numeric |
| Feature | SEO metadata | Response has `<title>`, canonical, og:title, twitter:card |
| Feature | Sitemap XML | Valid XML, includes public published, excludes private/unpublished |
| Feature | Widget JSON | Correct shape, organizer filter, limit enforcement, CORS header |
| Feature | Widget empty | Organizer with no published events → `events: []` |
| Feature | Widget invalid params | Missing organizer → 404; limit >20 → 422; limit <1 → 422 |

## Migration / Rollout

No data migration. Slug column already exists with unique constraint. Deploy routes + controllers atomically. Old numeric URLs get 301 immediately.

## Open Questions

None.
