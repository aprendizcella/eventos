# Design: TFM Public Section

## Technical Approach

Add an unauthenticated TFM section to the public site: a dropdown in navigation (desktop, mobile, footer), two Volt SFC pages (`/tfm/slides`, `/tfm/videos`) with hardcoded metadata, and a dedicated download controller for PPTX files from private storage. No DB or models — static content only, matching the existing public layout and Alpine.js navigation patterns.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|---|---|---|---|
| Component type | Volt SFC (`new class extends Component`) | Class-based Livewire, Blade-only | Matches existing `event-list-public.blade.php` convention — layout attribute, self-contained PHP+Blade |
| Storage disk | `local` (private, `storage/app/private`) | `public` disk, S3 | Defense in depth: files are never publicly accessible via URL; download is gated by controller allow-list |
| File allow-list | Hardcoded array in controller | DB table, config array | Two static files only — no DB overhead; controller is the single source of truth for approved filenames |
| Nav dropdown mechanism | Alpine.js `x-data="{ open: false }"` | Livewire dropdown, pure CSS | Matches existing topbar user menu pattern; no Livewire overhead for static nav items |
| Slide metadata storage | Hardcoded PHP array in Volt class | DB, JSON file, config | Two slides only — static data avoids schema, migration, and admin UI per scope |

## Data Flow

```
Visitor ──→ GET /tfm/slides ──→ Volt SFC (slide-list.blade.php) ──→ Card grid with PDF iframe + download link
                ↓
         GET /tfm/slides/{file}/download ──→ TfmSlideDownloadController ──→ Storage::disk('local')->get('tfm/slides/{filename}')
                ↓
         Returns StreamedResponse (attachment, Content-Type from MIME)

Visitor ──→ GET /tfm/videos ──→ Volt SFC (video-list.blade.php) ──→ Card with youtube-nocookie.com iframe
```

## File Changes

| File | Action | Description |
|---|---|---|
| `routes/web.php` | Modify | Add 3 TFM routes before existing routes to avoid collisions |
| `resources/views/layouts/public.blade.php` | Modify | Insert TFM dropdown in `$navLinks` array for desktop, mobile, and footer |
| `resources/views/livewire/public/tfm/slide-list.blade.php` | Create | Volt SFC: 2 slide cards with PDF iframe preview + PPTX download button |
| `resources/views/livewire/public/tfm/video-list.blade.php` | Create | Volt SFC: 1 video card with YouTube-nocookie embed |
| `app/Http/Controllers/Public/TfmSlideDownloadController.php` | Create | Invocable: validates allow-list, streams PPTX from private disk |
| `tests/Feature/Public/TfmSectionTest.php` | Create | Feature tests for routes, PDF preview, YouTube embed, download security |
| `tests/Feature/PublicNavigationTest.php` | Modify | Assert TFM dropdown in header, mobile drawer, and footer |
| `.gitignore` | Modify | Add `storage/app/private/tfm/slides/*.pptx` and `*.pdf` |

## Interfaces / Contracts

```php
// TfmSlideDownloadController — invocable, no contract interface needed
final class TfmSlideDownloadController
{
    public function __invoke(string $file): BinaryFileResponse|NotFoundHttpException
}
```

**Allow-list**: `['Presentacion_Demo_TFM_Eventos.pptx', 'Presentacion_TFM_Eventos_Multitenant.pptx']`

**PDF filenames**: derived from PPTX — same basename, `.pdf` extension (e.g., `Presentacion_Demo_TFM_Eventos.pdf`).

**Disk path**: `Storage::disk('local')->path('tfm/slides/')` → resolves to `storage/app/private/tfm/slides/`.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature | `TfmSectionTest` | GET slides/videos return 200; PDF iframe present; YouTube embed uses `youtube-nocookie.com`; approved PPTX downloads; unknown filename returns 404 |
| Feature | `PublicNavigationTest` (modified) | Assert `tfm*` links in header desktop nav, mobile drawer HTML, and footer; assert `aria-current="page"` when on a TFM page |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No migration required. New routes and files are additive — no existing behavior changes. Rollback: revert route additions, revert `public.blade.php` nav and footer, delete 3 new files, revert test additions.

## Open Questions

None. All decisions are resolved by existing codebase patterns and spec requirements.
