# Tasks: Discovery-First Public Event Landing

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 750–1,050 |
| 400-line budget risk | High |
| 5,000-line approved reviewer budget risk | Low |
| Chained PRs recommended | Yes — direct-to-main work units only |
| Delivery strategy | ask-on-risk |
| Chain strategy | stacked-to-main, isolated direct commits |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Direct commit | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Catalog Features | `feat(public-catalog): add verified feature sections` | `vendor/bin/sail artisan test --compact tests/Feature/Catalog/PublicCatalogTest.php` | `/` on root/organizer hosts | Catalog view and test |
| 2 | Shell and Docs | `feat(public-site): add navigation docs and footer` | `vendor/bin/sail artisan test --compact tests/Feature/PublicNavigationTest.php` | `/` and `/docs`, guest/user | Layout, Docs route/view, test |
| 3 | Mobile accessibility | `feat(public-site): harden mobile menu accessibility` | `vendor/bin/sail artisan test --compact tests/Browser/PublicNavigationTest.php` | Mobile: keyboard, Escape, focus | Browser test and mobile menu |

## Phase 1: Catalog Safety and Features

- [x] 1.1 **RED** — Extend `tests/Feature/Catalog/PublicCatalogTest.php`: guest `/`, catalog-first order, Discover→`/`, URL state, organizer-only published scope, and no marketplace/native-app/integration/deferred-doc claims. *(Public Catalog and Features; `/` and organizer threats.)*
- [x] 1.2 **GREEN** — Update `resources/views/livewire/public/events/event-list-public.blade.php`: append verified attendee/organizer `#features` after pagination; preserve catalog query behavior.

## Phase 2: Public Shell and Docs

- [ ] 2.1 **RED** — Create `tests/Feature/PublicNavigationTest.php`: guest/auth destinations, exact GitHub href/text/`target`/`rel`, Login/Dashboard exclusivity, footer parity, and theme markers. *(Navigation, Footer; guest/auth, GitHub, theme threats.)*
- [ ] 2.2 **GREEN** — Modify `resources/views/layouts/public.blade.php` with semantic desktop/mobile navigation, retained theme component, auth action, safe external link, and footer.
- [ ] 2.3 **RED** — Assert guest `/docs`, exactly the three named categories, non-linking cards/no guide URLs, and distinct Technical Reference scope. *(Docs landing/scope; `/docs` threat.)*
- [ ] 2.4 **GREEN** — Add named `/docs` in `routes/web.php`; create `resources/views/public/docs/index.blade.php` with bounded category-overview copy.

## Phase 3: Mobile Accessibility and Verification

- [ ] 3.1 **RED** — Confirm Pest Browser APIs; create `tests/Browser/PublicNavigationTest.php` for equivalent mobile destinations, `aria-expanded`, keyboard open, Escape close, focus return, and persisted theme if supported. *(Responsive Navigation; mobile/theme threats.)*
- [ ] 3.2 **GREEN** — Refine `resources/views/layouts/public.blade.php` Alpine-compatible state, `x-cloak`, Escape, and menu-control focus restoration; retain theme persistence.
- [ ] 3.3 Run the three focused suites, then `vendor/bin/sail composer run pint -- --test` and `vendor/bin/sail composer run phpstan`; record results with each direct commit.
