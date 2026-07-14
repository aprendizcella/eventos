# Design: Sprint 5.2 — Search w/ Scout + Meilisearch & UX Discovery

## Technical Approach

Hybrid search: a dedicated search service uses Laravel Scout + Meilisearch for full-text and falls back to Eloquent/LIKE when the engine is unavailable. The service keeps tenant and structured filter rules explicit, then hydrates Eloquent models with eager-loaded relations. Volt orchestrates extracted Blade components. Indexing dispatches asynchronously through the existing Horizon queues after commit.

## Architecture Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Engine | Scout + Meilisearch | Container exists in `compose.yaml`; Scout abstracts engine swap |
| Scope | title + description searchable; organizer/category/city/start date filterable | Structured attributes support tenant and catalog filters without affecting text relevance |
| Eligibility | published + public only | `shouldBeSearchable()` plus explicit removal on lifecycle changes |
| Index removal | Explicit Scout unsearchable operation | Prevents private/unpublished records remaining searchable after queued updates |
| Fallback | Dedicated service with Eloquent WHERE LIKE on title/desc | Logs failure; avoids pretending Scout has a native runtime fallback driver |
| Debounce | 400ms via `wire:model.live.debounce.400ms` | Balances responsiveness with request volume |
| URL sync | `#[Url]` on filter/search properties | Framework-native; restores state on load |
| Pagination | `->paginate(12)` | Per spec: no infinite scroll |
| Ordering | Preserve Meilisearch result order for text search; date ASC without query | Avoids losing relevance when hydrating Eloquent models |
| Catalog components | `x-catalog.*` not `x-form.*` | Per spec: catalog-specific |
| Card price | Min product price / Sold out | Spec: min available or Sold out |
| Breadcrumb | Add public breadcrumb rendering to detail and an optional public layout slot | Existing admin breadcrumbs are not public-compatible |

## Data Flow

```
── SEARCH ──

  User types ──wire:model.live.debounce.400ms──→ Volt Component
    │ query? ──Yes──→ EventSearchService ──→ Scout/Meilisearch
    │                         └──────────────→ Eloquent LIKE fallback
    └── No  ──→ (skip Scout)
    │
    ▼
  Meilisearch applies tenant/category/city/date filterable attributes
    → preserve relevance order when text exists
    → date ASC when text is absent
    → hydrate/paginate 12 results

Date-range filtering is represented as a filterable `starts_at` timestamp in the Meilisearch index. The Eloquent fallback/hydration path remains responsible for applying the final date predicate when the active Scout engine cannot express the required range operator.


── INDEX (async after commit) ──

  Event create/update ──→ Searchable observer / lifecycle sync
    → after-commit queue → Horizon → Meilisearch upsert/delete

  State change to non-published/public:
    explicit unsearchable operation removes the record
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add `laravel/scout`, `meilisearch/meilisearch-php`, and `http-interop/http-factory-guzzle` |
| `config/scout.php` | Create | Scout configuration with Meilisearch driver and queue settings |
| `.env.example` | Modify | Add `MEILISEARCH_HOST`, `MEILISEARCH_KEY`, `SCOUT_DRIVER` |
| `app/Models/Event.php` | Modify | Add `Searchable`, searchable payload, eligibility, and removal behavior |
| `app/Services/Discovery/EventSearchService.php` | Create | Orchestrate Scout search, structured filters, relevance order, and Eloquent fallback |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Modify | Add `$search`, `$search` property with `#[Url]`, debounced wire model, pagination, extracted components |
| `resources/views/components/catalog/search-bar.blade.php` | Create | Search input with debounce wire model binding |
| `resources/views/components/catalog/filter-bar.blade.php` | Create | Filter group wrapper (extracted from Volt) |
| `resources/views/components/catalog/filter-chip.blade.php` | Create | Active filter chip with remove action |
| `resources/views/components/catalog/result-summary.blade.php` | Create | "Showing X of Y" + Clear/Reset contextual actions |
| `resources/views/components/catalog/skeleton-card.blade.php` | Create | Loading placeholder matching event-card layout |
| `resources/views/components/catalog/event-card.blade.php` | Modify | Add min price from products, Sold out state, image slot placeholder |
| `resources/views/layouts/public.blade.php` | Modify | Add optional public breadcrumb slot above main |

## Interfaces / Contracts

```php
// Event model Scout contract
public function toSearchableArray(): array {
    return [
        'title' => $this->title,
        'description' => $this->description,
        'organizer_id' => $this->organizer_id,
        'category_id' => $this->category_id,
        'venue_city' => $this->venue?->city,
        'starts_at' => $this->starts_at?->timestamp,
    ];
}

public function shouldBeSearchable(): bool {
    return $this->status === EventStatus::Published
        && $this->visibility === EventVisibility::Public;
}

// Meilisearch settings: searchableAttributes = title, description;
// filterableAttributes = organizer_id, category_id, venue_city, starts_at.
```

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Unit | `toSearchableArray()` | Assert title/description are searchable and structured filter attributes are present but not free-text fields |
| Feature | Search + filter combos | Scout `database` driver; Livewire `set('search', ...)` + filter asserts |
| Feature | Index sync | Assert after-commit queue pushes on create/update; assert removal on unpublish |
| Feature | Pagination | 13+ events, assert page 1 shows 12 |
| Feature | Empty state | Assert Clear/Reset buttons when search/filters active |
| Feature | Event card price | Factory with products; assert min and Sold out |
| Integration | Real Meilisearch | Targeted test when `MEILISEARCH_HOST` set; skipped otherwise |

## Migration / Rollout

No data migration required. Run `php artisan scout:sync-index-settings` and `php artisan scout:import "App\Models\Event"` after deploy. Meilisearch already runs in `compose.yaml`; the application fallback remains available if the service is unavailable.

## Open Questions

None. All decisions confirmed by spec.
