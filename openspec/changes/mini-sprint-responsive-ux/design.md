# Design: Mini-Sprint Responsive & Reactive UX (Livewire Volt)

## Technical Approach

We will build interactive tables using Livewire Volt Single File Components (SFCs).
By adopting Livewire, we keep all database interactions, search parameters, pagination state, and ordering operations in a clean PHP class state.
Visibilities of columns will be toggled reactively using a checklist dropdown in the table toolbar. The visibility state is bound to a `$visibleColumns` array in the component. Paging, changing page sizes (per-page entries), and search filtering are processed via Livewire's AJAX pipeline.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Livewire Volt vs Blade + Alpine.js | Livewire requires components rewrite, but eliminates architectural hybrid splits, prevents technical debt, and simplifies future inline actions. | Use Livewire Volt class-based SFC components. |
| Hybrid mounting vs full routing | Routing fully through Livewire requires updating `routes/web.php` and rewriting page layout wrappers. Hybrid mounting keeps controllers/middleware intact. | Keep routes and controllers; mount Volt components inside existing Blade indexes. |

## Data Flow

```text
User Search/Sort/Page -> Livewire AJAX request -> Volt Component Class -> Eloquent Query -> Blade Render
User Checkbox Toggle -> Livewire state visibleColumns update -> DOM reactive update (x-show / blade checks)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `resources/views/livewire/organizers/organizers-table.blade.php` | Create | Volt class and layout for global organizers index. |
| `resources/views/livewire/organizers/team-table.blade.php` | Create | Volt class and layout for team management, including inline modal properties. |
| `resources/views/livewire/organizers/events-table.blade.php` | Create | Volt class and layout for events list, containing status/visibility/dates query filters. |
| `resources/views/livewire/organizers/venues-table.blade.php` | Create | Volt class and layout for venues list. |
| `resources/views/organizers/team/index.blade.php` | Modify | Replaced by `<livewire:organizers.team-table :organizer="$organizer" />`. |
| `resources/views/organizers/events/index.blade.php` | Modify | Replaced by `<livewire:organizers.events-table :organizer="$organizer" />`. |
| `resources/views/organizers/index.blade.php` | Modify | Replaced by `<livewire:organizers.organizers-table />`. |
| `resources/views/organizers/venues/index.blade.php` | Modify | Replaced by `<livewire:organizers.venues-table :organizer="$organizer" />`. |
| `resources/views/components/ui/table.blade.php` | Delete | Deprecated and removed. |

## Testing Strategy

All existing feature tests checking authorizations, controllers, and responses should continue to pass.
Additionally, we verify reactiveness manually in the browser.
