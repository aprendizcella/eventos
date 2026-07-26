# Proposal: Mini-Sprint Responsive & Reactive UX (Livewire Volt)

## Intent

Improve administrative user experience and eliminate future technical debt by ensuring full responsive usability down to 320px, aligning data tables and modals with TailAdmin visual styles, and building reactive table components using Laravel Livewire Volt.

## Scope

### In Scope
- Create reusable, interactive tables using Livewire Volt under `resources/views/livewire/organizers/`:
  - `organizers-table.blade.php`: Global organizers index.
  - `team-table.blade.php`: Team members index with reactive modals.
  - `events-table.blade.php`: Events index with reactive search, sorting, and collapsible filters.
  - `venues-table.blade.php`: Venues index.
- Add support for real-time AJAX pagination, sorting by columns, "Show entries" perPage selector, and column visibility toggle dropdown.
- Maintain existing routes, controllers, middleware, and policy authorization guards.
- Integrate modal support (click outside close, escape key, scrollable, max-width sizing) with Alpine.js inside the Volt components.
- Delete the deprecated `<x-ui.table>` Blade component.

### Out of Scope
- Rewriting edit/create forms under Livewire Volt (retained as standard HTML forms).
- Changing routes structure in `routes/web.php`.

## Capabilities

### New Capabilities
- `livewire-tables`: reactive lists with dynamic sorting, pagination, and column visibility.

### Modified Capabilities
- `organizer-management`: responsive index, team, show, and venue views.
- `event-management`: responsive events index with collapsable search filters.

## Approach

Use Livewire Volt class-based SFC components mounted inside existing Blade templates. Visibilities of columns are toggled in real-time. Paginating, sorting, and changing perPage entries are processed instantly using AJAX requests without page reloads.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/livewire/organizers/` | New | 4 new Livewire Volt components. |
| `resources/views/organizers/team/index.blade.php` | Modified | Mount `team-table` component. |
| `resources/views/organizers/events/index.blade.php` | Modified | Mount `events-table` component. |
| `resources/views/organizers/index.blade.php` | Modified | Mount `organizers-table` component. |
| `resources/views/organizers/venues/index.blade.php` | Modified | Mount `venues-table` component. |
| `resources/views/components/ui/table.blade.php` | Delete | Clean up obsolete component. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Multi-organizer access leak | Low | Secure query isolation using `$this->organizer->events()` or scopes inside Volt class state. |
| Component size exceeds budget | Med | Write thin class components and delegate rendering layout patterns to Blade structures. |

## Rollback Plan

Revert responsive/reactive UX commits. Restore previous Blade table and controller queries.

## Success Criteria

- [ ] All tables in target views use Livewire Volt components.
- [ ] Sorting, search, and pagination happen instantly via AJAX.
- [ ] Columns can be hidden/shown dynamically from a checklist dropdown.
- [ ] Existing Pest test coverage remains 100% green.
