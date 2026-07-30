# Exploration: TFM Public Section

## Topic
Add a new public "TFM" section to the Laravel 12 application with a dropdown navigation item, two sub-pages (Slides and Videos), PowerPoint downloads stored securely, and a YouTube embed, all following existing public-page conventions.

## Current State

### Public Navigation Architecture
- The public layout is defined in `resources/views/layouts/public.blade.php`.
- Navigation links are hard-coded as a PHP array (`$navLinks`) inside the layout, then looped into a desktop nav and a mobile drawer.
- There is no separate navigation component or dropdown menu on the public side today; every link is a flat anchor.
- The authenticated app shell uses `resources/views/components/navigation/sidebar.blade.php` and `topbar.blade.php`; dropdowns only exist in the topbar user menu (Alpine.js `x-data="{ open: false }"` pattern).
- Footer links are also hard-coded and mirror the nav links.

### Public Route Patterns
- Public routes live in `routes/web.php`.
- Existing public pages use `Volt::route(..., 'public.{domain}.{component}')->name('public.{domain}.{action}')` (e.g. `public.events.event-list-public` → `public.events.catalog`).
- Static views use `Route::view('/docs', 'public.docs.index')->name('public.docs.index')`.
- Public routes are not under a `/public` prefix; they sit at the root (`/`, `/docs`, `/events/{slug}`).

### Existing Livewire Component Patterns
- No classes exist under `app/Livewire/`; the project uses Volt single-file components (SFC) stored in `resources/views/livewire/`.
- `config/livewire.php` confirms: `make_command.type` = `sfc`, `emoji` = `true`, default layout is `layouts::app`.
- Public page components use `new #[Layout('layouts.public')] class extends Component { ... }` at the top of the SFC.
- Example pattern: `resources/views/livewire/public/events/event-list-public.blade.php` (full-page, public layout, URL-synced state, computed properties).
- `Route::view` is used for truly static pages (`/docs`), but the user explicitly asked to reuse Livewire patterns, so Volt SFC page components are preferred.

### File Storage & Downloads
- `config/filesystems.php` defines:
  - `local` disk → `storage_path('app/private')` (serve=true, visibility=private)
  - `public` disk → `storage_path('app/public')` with URL `/storage` (visibility=public)
  - `links` maps `public_path('storage') => storage_path('app/public')`
- `storage/app/public/` is empty except `.gitignore`.
- There is no existing generic public file-download controller. The closest pattern is `DownloadInvoiceController`, which authorizes, then delegates to a service that returns a `Response` (PDF download). For public PPTX files, a dedicated `TfmSlideDownloadController` should stream from the `local` disk so files are not directly web-accessible.

### UX Patterns for Cards
- Cards use `rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900` consistently (seen in docs index, event detail sidebar, calendar actions).
- Event cards add `overflow-hidden` and hover states (`hover:shadow-md hover:border-blue-300`).
- Buttons: `resources/views/components/ui/button.blade.php` (blue CTA) and `resources/views/components/ui/link.blade.php`.
- Headings: `text-3xl font-extrabold text-gray-900 dark:text-white` for page titles, `text-lg font-semibold` for card titles.
- Body text: `text-sm leading-6 text-gray-600 dark:text-gray-400`.
- Badges: rounded-full blue background with blue text.
- Dark mode is fully supported via `dark:` variants.

### Public Layout
- `layouts.public` provides header, `<main class="... max-w-7xl ... py-10">`, and footer.
- New pages should extend `layouts.public` and render into the `$slot`/`content` area.
- The new "TFM" dropdown will be inserted into the `$navLinks` array logic in the public layout, plus the mobile drawer and footer.

## Affected Areas

| File / Path | Why Affected |
|-------------|--------------|
| `routes/web.php` | Add `/tfm/slides` and `/tfm/videos` routes plus download route. |
| `resources/views/layouts/public.blade.php` | Add "TFM" dropdown to desktop nav, mobile drawer, and footer. |
| `resources/views/livewire/public/tfm/slide-list.blade.php` | New Volt SFC page listing slide cards with metadata and download links. |
| `resources/views/livewire/public/tfm/video-list.blade.php` | New Volt SFC page embedding the YouTube video in a card. |
| `app/Http/Controllers/Public/TfmSlideDownloadController.php` | Secure download endpoint for PPTX files from `local` storage. |
| `storage/app/public/tfm/slides/` (or `storage/app/private/...`) | Destination for the two PowerPoint files. |
| `tests/Feature/Public/TfmSectionTest.php` | Feature tests for routes, nav, downloads, and video embed. |
| `tests/Feature/PublicNavigationTest.php` | Update assertions for new TFM nav item. |

## Approaches

### Approach A: Livewire Volt SFC Pages + Dedicated Download Controller
Create two Volt SFC public page components (`public.tfm.slide-list`, `public.tfm.video-list`) wired to `/tfm/slides` and `/tfm/videos`. Each component extends `Component` with `#[Layout('layouts.public')]`, prepares its own data (file metadata via `Storage::disk('local')`, hard-coded YouTube iframe URL), and renders cards matching the existing Tailwind card pattern. Add a `TfmSlideDownloadController` invoked by a named route that streams PPTX files from the `local` disk with proper headers.

- **Pros:** Matches existing public-page conventions (Volt SFC), keeps logic testable, secure downloads, reusable card markup, explicit route names.
- **Cons:** Slightly more files than a pure static view; download controller must be tested.
- **Effort:** Low–Medium.

### Approach B: Static Blade Views + Direct Storage Links
Create `resources/views/public/tfm/slides.blade.php` and `videos.blade.php` extending `layouts.public`. Link directly to `/storage/tfm/slides/...` after copying files to `storage/app/public` and running `storage:link`.

- **Pros:** Minimal PHP logic, fastest to implement.
- **Cons:** Files become publicly enumerable if directory indexing is misconfigured; no metadata computed from filesystem; does not reuse Livewire patterns as requested; no authorization/audit point for downloads.
- **Effort:** Low.

### Approach C: Database-Driven Resource Model
Introduce a `TfmResource` Eloquent model + migration + factory to store title, description, file path, type (slide/video), publication date, and modification date. Seed it with the two slides and one video, then build generic index/show pages.

- **Pros:** Most flexible for future resources; admin can add more items without code changes.
- **Cons:** Over-engineered for three static assets; adds migration/factory/seeder/test surface; user did not ask for an admin UI. The files themselves are still fixed deliverables.
- **Effort:** High.

## Recommendation

Use **Approach A**.

Rationale:
- It directly satisfies the requirement to "reuse existing Livewire patterns."
- It keeps the public surface consistent with the rest of the app (Volt SFC + `layouts.public`).
- It avoids exposing files directly under `/storage`, satisfying the secure-download requirement.
- It is the smallest change that covers all requested functional and quality goals.

## Implementation Notes

### Navigation Dropdown
- Convert the flat `$navLinks` array to support nested children for the TFM item, or inline a separate TFM dropdown block between the Docs link and the GitHub link.
- Use the same Alpine.js pattern already in `topbar.blade.php`: `<div x-data="{ open: false }">` with a toggle button, `x-show="open"`, `@click.outside="open = false"`, and absolute-positioned panel.
- Mark the active state with `aria-current="page"` when `request()->is('tfm*')`.
- Mirror the TFM links in the mobile drawer and footer.

### Routes
```php
Volt::route('/tfm/slides', 'public.tfm.slide-list')->name('public.tfm.slides');
Volt::route('/tfm/videos', 'public.tfm.video-list')->name('public.tfm.videos');
Route::get('/tfm/slides/{file}/download', \App\Http\Controllers\Public\TfmSlideDownloadController::class)
    ->name('public.tfm.slides.download');
```
Use a strict `where` regex or a hard-coded allow-list in the controller so only `Presentacion_Demo_TFM_Eventos.pptx` and `Presentacion_TFM_Eventos_Multitenant.pptx` can be downloaded.

### Storage
- Copy the two `.pptx` files into `storage/app/private/tfm/slides/` (preferred) or `storage/app/public/tfm/slides/` (if using the public disk).
- If placed under the `public` disk, the download controller can still stream via `Storage::disk('public')->download()` while preventing directory browsing.
- Prefer `local`/`private` disk for defense in depth.
- File copy is a one-time setup step; document it in the apply phase and verify with a test that the expected files exist.

### Livewire Components
- `slide-list.blade.php`:
  - Define an array of slide metadata (title, description, filename, published_at, updated_at).
  - Read actual `updated_at` via `Storage::disk('local')->lastModified()` for the modification date.
  - Render a grid of cards; each card shows title, description, publication date, modification date, and a download link.
- `video-list.blade.php`:
  - Render one card with the YouTube embed using the privacy-enhanced `https://www.youtube-nocookie.com/embed/-NB4gIeLaKA` URL.
  - Include a short description and publication date.

### Testing
- Add `tests/Feature/Public/TfmSectionTest.php`:
  - `GET /tfm/slides` → 200 and sees slide titles.
  - `GET /tfm/videos` → 200 and sees embedded YouTube iframe.
  - `GET /tfm/slides/{file}/download` → 200 and attachment headers for allowed files; 404 for unknown files.
- Update `tests/Feature/PublicNavigationTest.php` to assert TFM dropdown/links appear in header and footer.

## Risks

- **Navigation divergence:** Adding the first dropdown to the public nav requires careful Alpine.js markup to keep desktop and mobile experiences consistent and accessible.
- **Storage path mismatch:** The PPTX files must be copied to the correct disk and path; tests should assert the expected files exist before download assertions.
- **Git / large files:** Each `.pptx` is ~3.4 MB. Ensure they are not committed unintentionally; update `.gitignore` if they are placed under `storage/app/public` and should be deployed separately.
- **YouTube iframe CSP:** If a strict Content Security Policy is added later, `frame-src` must include `https://www.youtube-nocookie.com`. Current `config/livewire.php` has `csp_safe => false`, so no immediate action.
- **Route ordering:** Place static `/tfm/slides` and `/tfm/videos` before any dynamic `/tfm/slides/{file}` route to avoid parameter collision.

## Ready for Proposal

Yes. The exploration has identified the exact insertion points, existing patterns, and a recommended low-friction approach. The next step is `sdd-propose` to formalize scope, route names, file storage decision, and rollback plan.
