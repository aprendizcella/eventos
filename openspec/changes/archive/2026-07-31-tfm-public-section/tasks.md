# Tasks: TFM Public Section

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 200–400 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR (`size:exception` for `single-pr` strategy) |
| Delivery strategy | single-pr |
| Chain strategy | size-exception |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Deliver public TFM pages, download, and navigation | Single PR | `vendor/bin/sail artisan test --compact tests/Feature/Public/TfmSectionTest.php tests/Feature/PublicNavigationTest.php` | Visit `/tfm/slides`, `/tfm/videos`, and an approved/unknown download URL | Revert TFM routes, controller, Volt pages, assets, nav, and tests |

## Phase 1: Foundation

- [x] 1.1 Create `storage/app/private/tfm/slides/`; copy both approved PPTX/PDF pairs from `docs/07-entrega-tfm/` without renaming.
- [x] 1.2 Update `.gitignore` to exclude `storage/app/private/tfm/slides/*.pptx` and `*.pdf`; confirm source deliverables remain tracked.

## Phase 2: Backend

- [x] 2.1 RED: Add failing download cases in `tests/Feature/Public/TfmSectionTest.php`: each allow-listed PPTX downloads; an unknown filename returns 404 without private-file disclosure.
- [x] 2.2 GREEN: Create invocable `app/Http/Controllers/Public/TfmSlideDownloadController.php` with the two-name allow-list and private-local-disk attachment response.
- [x] 2.3 GREEN: Register named public slide/video Volt routes and the constrained download route in `routes/web.php`, before conflicting dynamic routes.
- [x] 2.4 REFACTOR: Run the focused section test; simplify controller and route names while retaining allow-list-only access.

## Phase 3: Frontend

- [x] 3.1 RED: Add failing page assertions in `tests/Feature/Public/TfmSectionTest.php` for two slide cards, metadata, PDF previews/download links, and one `youtube-nocookie.com` video embed.
- [x] 3.2 GREEN: Create `resources/views/livewire/public/tfm/slide-list.blade.php` Volt SFC with two hardcoded metadata cards, inline PDFs, and named PPTX downloads.
- [x] 3.3 GREEN: Create `resources/views/livewire/public/tfm/video-list.blade.php` Volt SFC with one titled, described `youtube-nocookie.com` iframe card.
- [x] 3.4 REFACTOR: Run the focused section test and remove duplicate metadata/markup without changing required output.

## Phase 4: Navigation

- [x] 4.1 RED: Extend `tests/Feature/PublicNavigationTest.php` for TFM Slides/Videos in desktop, mobile drawer, and footer, including current-page state and retained theme/menu focus behavior.
- [x] 4.2 GREEN: Update `resources/views/layouts/public.blade.php` with an Alpine TFM dropdown between Docs and external GitHub plus equivalent mobile/footer links.
- [x] 4.3 REFACTOR: Run the focused navigation test; preserve existing keyboard, focus-return, ARIA, and persisted-theme behavior.

## Phase 5: Testing and Verification

- [x] 5.1 Run both feature files via the focused Sail command; verify 200 public pages, approved downloads, 404 rejection, PDF previews, and nocookie embed.
- [x] 5.2 Run `vendor/bin/sail composer run pint`, `vendor/bin/sail composer run phpstan`, and `vendor/bin/sail composer run test` before commit.
