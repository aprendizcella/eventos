# Proposal: TFM Public Section

## Intent

Add a public "TFM" dropdown to the site navigation with Slides and Videos sub-pages, showcasing the TFM deliverables. No auth required — these are public portfolio pages consistent with the existing public layout patterns.

## Scope

### In Scope
- TFM dropdown in desktop nav (between Docs and GitHub), mobile drawer, and footer
- `/tfm/slides` page: card grid with PDF inline preview + PPTX download for 2 presentations
- `/tfm/videos` page: card with YouTube-nocookie embed
- `TfmSlideDownloadController`: secure PPTX download from private storage disk
- Hardcoded metadata (title, description, dates) in each Volt component
- Feature tests for all routes, nav presence, and download security

### Out of Scope
- Database models, migrations, seeders, or admin UI for TFM resources
- Generic file download controller (only TFM slides)
- Non-PPTX download support
- Search, filtering, or pagination on TFM pages

## Capabilities

### New Capabilities
- `public-tfm-slides`: Slides list page with PDF preview cards and PPTX download, no auth required
- `public-tfm-videos`: Videos list page with YouTube embed card, no auth required

### Modified Capabilities
- `public-site-navigation`: Add TFM dropdown with Slides and Videos sub-items visible on all public pages; update GitHub link position reference

## Approach

Approach A from exploration: Volt SFC pages (`public.tfm.slide-list`, `public.tfm.video-list`) + dedicated `TfmSlideDownloadController`. Insert TFM dropdown using Alpine.js `x-data` pattern (matching existing topbar user menu). Files stored in `storage/app/private/tfm/slides/`. Routes registered before the dynamic download route to avoid collision.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | Add `Volt::route` for slides/videos + download route |
| `resources/views/layouts/public.blade.php` | Modified | TFM dropdown in desktop nav, mobile drawer, footer |
| `resources/views/livewire/public/tfm/slide-list.blade.php` | New | Volt SFC: slide cards with PDF iframe + download btn |
| `resources/views/livewire/public/tfm/video-list.blade.php` | New | Volt SFC: YouTube embed card |
| `app/Http/Controllers/Public/TfmSlideDownloadController.php` | New | Secure PPTX download from private disk |
| `tests/Feature/Public/TfmSectionTest.php` | New | Route, nav, download tests |
| `tests/Feature/PublicNavigationTest.php` | Modified | Assert TFM dropdown in nav/footer |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Nav dropdown inconsistent on mobile | Low | Use same Alpine pattern as topbar user menu |
| PPTX file missing at deployment | Low | Test asserts file exists before download |
| YouTube CSP block | Low | Use `youtube-nocookie.com`; note in docs for future CSP |
| Large PPTX in git | Low | Add `storage/app/private/tfm/*.pptx` to `.gitignore` |

## Rollback Plan

1. Revert route additions in `routes/web.php`
2. Revert nav changes in `public.blade.php`
3. Delete `TfmSlideDownloadController` and two Volt SFC files
4. Revert test files
5. No migrations or DB changes to roll back

## Dependencies

- Two `.pptx` files + their PDFs must exist in `docs/07-entrega-tfm/` before apply

## Success Criteria

- [ ] `GET /tfm/slides` returns 200 and renders PDF previews + download buttons
- [ ] `GET /tfm/videos` returns 200 and renders YouTube embed
- [ ] TFM dropdown appears in desktop nav, mobile drawer, and footer
- [ ] PPTX download streams correct file from private disk
- [ ] Unknown filenames return 404 from download controller
