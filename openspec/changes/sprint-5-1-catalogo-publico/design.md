# Design: Sprint 5.1 - Public Catalog

## Technical Approach

Build the public discovery surface as Livewire Volt components mounted on the existing public layout. The root domain will act as the global catalog, while organizer domains will automatically scope queries to the current tenant resolved by the existing host-first multitenancy finder.

## Architecture Decisions

| Option | Tradeoff | Decision |
|---|---|---|
| Separate global and tenant routes | Easier to read at routing level, but duplicates the public UX. | Keep one public catalog experience and scope by host. |
| Controller-driven pages | Familiar, but adds boilerplate for a highly interactive discovery surface. | Use Livewire Volt components for list and detail. |
| Early Scout/Meilisearch adoption | Better search later, but too much for Sprint 5.1. | Leave full-text search for Sprint 5.2. |

## Data Flow

```text
Request -> Tenant Finder -> Public Catalog Component -> Event Query -> Filters Applied -> Blade Render
Request -> Tenant Finder -> Public Detail Component -> Event Query -> Visibility Check -> Blade Render
```

## Public Scope Logic

- Root domain (`config('app.url')` host): query all published public events.
- Organizer domain: query only the current tenant's published public events.
- Hidden or unpublished events are excluded in both contexts.

## File Changes

| File | Action | Description |
|---|---|---|
| `routes/web.php` | Modify | Route the root page to the public catalog and add the public detail route. |
| `resources/views/layouts/public.blade.php` | Modify | Public shell may need catalog/detail-friendly layout polish. |
| `resources/views/livewire/public/events/event-list-public.blade.php` | Create | Public catalog list with filters and empty state. |
| `resources/views/livewire/public/events/event-detail-public.blade.php` | Create | Public event detail with checkout CTA and calendar actions. |
| `resources/views/livewire/public/events/event-card.blade.php` | Create | Reusable event card used by the catalog and future discovery surfaces. |

## Sequence

```text
User opens / -> host resolved -> scope determined -> public catalog query -> render cards
User opens /events/{event} -> host resolved -> public detail query -> visibility check -> render detail -> checkout CTA
```

## Testing Strategy

- Verify root-domain catalog scope.
- Verify organizer-domain scope.
- Verify only public/published events are shown.
- Verify filters affect the result set.
- Verify public detail does not require login and links to checkout.
