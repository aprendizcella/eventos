# Design: Discovery-First Public Event Landing

## Technical Approach

Keep the existing Volt catalog route, `public.events.catalog`, at `/` and place concise Features content after its current discovery UI. Extend the shared public layout for navigation and footer, and add a planned public `/docs` index using that layout. No catalog query, tenant resolution, or persisted theme implementation changes.

## Architecture Decisions

| Decision | Alternatives | Rationale |
|---|---|---|
| Preserve `/`; append `#features` after pagination | Marketing home or a new catalog route | `routes/web.php:25` and `event-list-public.blade.php:19-34,161-286` establish the Volt catalog, URL state, and discovery-first content. |
| Put shared navigation/footer in `layouts.public` | Duplicate per view | The catalog already selects this layout (`event-list-public.blade.php:19`); it already renders `@auth`, Login/Dashboard routes, and the theme component (`layouts/public.blade.php:30-45`). |
| Add a named public Docs route and static index | External docs or detailed guides | Named routes are established in `routes/web.php`; no `/docs` route or product docs view exists. The index must explicitly label its cards as category overviews, not links to deferred content. |
| Reuse Alpine-compatible markup and existing theme control | New JS package/state | Alpine is a declared dependency (`package.json:17-19`) and is started by Livewire (`resources/js/app.js:3-4`); `x-cloak` and class-based dark mode exist in `resources/css/app.css:3,10-12`. Theme persistence remains solely in `components/ui/theme-*.blade.php`. |

## Data Flow

```text
GET /      -> Volt catalog -> EventSearchService -> scoped public events
              -> public layout -> navigation/theme/footer
GET /docs  -> planned Docs view -> public layout
```

Discover Events and the brand target `/`; Features targets `/#features`; Docs targets planned `/docs`. The GitHub anchor targets the confirmed origin without its `.git` suffix, `https://github.com/aprendizcella/eventos`, and is visibly identified as external. Follow the existing external-link security pattern: `target="_blank" rel="noopener noreferrer"` (`event-detail-public.blade.php:161`).

## File Changes

| File | Action | Description |
|---|---|---|
| `routes/web.php` | Modify | Add named public `/docs` route. |
| `resources/views/layouts/public.blade.php` | Modify | Shared desktop/mobile navigation and destination footer; retain auth and theme components. |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Modify | Add attendee/organizer `#features` after the catalog. |
| `resources/views/public/docs/index.blade.php` | Create | Introduction plus non-deferred category cards. |
| `tests/Feature/Catalog/PublicCatalogTest.php` | Modify | Protect catalog-first and tenant-scope behavior. |
| `tests/Feature/PublicNavigationTest.php` | Create | HTTP/rendered markup contract for shell, Docs, footer, states, and theme markup. |
| `tests/Browser/PublicNavigationTest.php` | Create | Mobile keyboard/focus contract, subject to installed browser capability. |

## Requirement Traceability

| Requirement / scenario | Design and evidence | Planned RED test |
|---|---|---|
| Catalog route: guest; return to discovery; organizer scope | `/`, catalog-first order, brand/Discover targets, `#features`; current scope is tested by `PublicCatalogTest.php:23-113` and `PublicCatalogSearchTest.php:189-213`. | Guest `GET /`; rendered order/targets; current-organizer Features-to-catalog regression. |
| Navigation destinations: guest desktop; authenticated | Layout exposes brand→`/`, Discover, Features, Docs, external GitHub, theme, Login/Dashboard. Current auth branches/routes: `layouts/public.blade.php:30-40`, `routes/web.php:36,86`. | Guest and `actingAs` responses assert each destination and Login/Dashboard exclusivity. |
| Accessible responsive navigation: keyboard; persisted theme | Native menu button, `aria-expanded`, controlled menu, Escape focus return; reuse `theme-toggle.blade.php:5-103` and `theme-init.blade.php:3-8`. | Render ARIA/theme markers; browser test verifies keyboard/focus only after confirming its API in the installed plugin. |
| Verified Features: visitor reviews | Separate attendee/organizer sections after pagination; claims limited to routes/components evidenced by catalog, checkout/waitlist/orders (`routes/web.php:43-57`), organizer event/venue/report routes (`routes/web.php:123-150`), sitemap (`:34`), and calendar link (`event-detail-public.blade.php:156-168`). | Assert headings, order, and absence of marketplace/native-app/integration/deferred-doc claims. |
| Footer: visitor uses footer | Footer repeats available destinations without changing targets. | Assert footer destinations and exact hrefs. |
| Docs landing: guest opens Docs | Named `/docs` index with exactly Getting Started, Help Center Workflows, Technical Reference. | Guest `GET /docs` is successful and exposes three categories. |
| Docs scope: category; reference types | Cards are non-linking/category-overview content; Technical Reference is distinguished from workflows. | Assert no detailed-guide/category URLs and scope labels. |

## Testing Strategy

Pest HTTP and Livewire assertions are established (`tests/Feature/Catalog/PublicCatalogTest.php`, `tests/Feature/AdminLayoutTest.php`). The browser plugin is installed (`composer.json:42-45`), but no repository browser test or helper usage exists; browser assertions must be validated during implementation rather than naming unverified helpers here. All tests start RED under `openspec/config.yaml:32,42-45`.

## Threat Matrix

| Boundary | Applicability | Safe / failure behavior | RED test |
|---|---|---|---|
| `/` catalog | Applicable | Preserve guest access, discovery-first order, URL state, and current organizer scope; no regression. | Root and current-organizer catalog cases. |
| `/docs` | Applicable | Public index only; no deferred guides or tenant content. | Guest Docs response and category boundary. |
| Organizer domains | Applicable | Features/navigation must not broaden eligible catalog events. | Current-organizer catalog regression. |
| External GitHub | Applicable | Exact origin-derived URL, external identification, safe new-tab relation. | Rendered href/text/attributes. |
| Guest/auth state | Applicable | Login for guests; Dashboard for authenticated users. | Guest and authenticated responses. |
| Mobile accessibility | Applicable | Equivalent destinations, keyboard operation, exposed state, focus restoration. | Browser test after capability validation. |
| Theme persistence | Applicable | Keep existing localStorage/system behavior and theme control on both layouts. | Rendered theme markers; browser persistence if supported. |
| Shell/VCS/commit/push/PR/process execution | N/A | This is HTTP/UI work; it invokes no shell, VCS, subprocess, or process integration. | None. |

## Migration / Rollout

No migration required. Rollback reverts route/view/layout changes only.

## Open Questions

None.
