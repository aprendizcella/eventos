# Tasks: Mini-Sprint Responsive & Reactive UX (Livewire Volt)

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 400-500 |
| 400-line budget risk | Medium-High |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | size-exception |
| Chain strategy | not applicable |

## Phase 1: Livewire Volt Components Creation

- [x] 1.1 Create `resources/views/livewire/organizers/organizers-table.blade.php`.
- [x] 1.2 Create `resources/views/livewire/organizers/team-table.blade.php`.
- [x] 1.3 Create `resources/views/livewire/organizers/events-table.blade.php`.
- [x] 1.4 Create `resources/views/livewire/organizers/venues-table.blade.php`.

## Phase 2: Views Integration

- [x] 2.1 Update `resources/views/organizers/index.blade.php` to mount `<livewire:organizers.organizers-table />`.
- [x] 2.2 Update `resources/views/organizers/team/index.blade.php` to mount `<livewire:organizers.team-table :organizer="$organizer" />`.
- [x] 2.3 Update `resources/views/organizers/events/index.blade.php` to mount `<livewire:organizers.events-table :organizer="$organizer" />`.
- [x] 2.4 Update `resources/views/organizers/venues/index.blade.php` to mount `<livewire:organizers.venues-table :organizer="$organizer" />`.

## Phase 3: Cleanup & Refactoring

- [x] 3.1 Delete `resources/views/components/ui/table.blade.php`.

## Phase 4: QA & Verification

- [x] 4.1 Run Pint formatter (`vendor/bin/sail bin pint --dirty --format agent`).
- [x] 4.2 Run PHPStan static analysis (`vendor/bin/sail composer run phpstan`).
- [x] 4.3 Run Pest tests (`vendor/bin/sail composer run test`).
- [ ] 4.4 Verify manually on browser.
