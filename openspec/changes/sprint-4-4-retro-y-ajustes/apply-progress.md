# Apply Progress: sprint-4-4-retro-y-ajustes

**Date**: 2026-07-12
**Phase**: Phase 1 (Foundation) + Phase 2 (Core Implementation)
**Mode**: Standard
**Delivery Strategy**: exception-ok (direct commits to main)

## Completed Tasks

### Phase 1: Foundation

- [x] **1.1** — Created `app/Providers/HorizonServiceProvider.php` with `Horizon::auth()` gate checking `super_admin` / `platform_admin` roles via Spatie `hasRole()`. Registered in `bootstrap/providers.php` by `horizon:install` command.
- [x] **1.2** — Updated `config/horizon.php` with named queues (`tickets`, `emails`, `default`), balanced weights (3:2:1), and wait time thresholds. Updated `config/queue.php` to default to `redis`. Added `Schedule::command('horizon:snapshot')->everyFiveMinutes()` to `routes/console.php`.

### Phase 2: Core Implementation

- [x] **2.1** — Installed `laravel/horizon` via `composer require` (v5.47.2). Updated `.env.example` with `QUEUE_CONNECTION=redis`. Confirmed `phpunit.xml` stays `QUEUE_CONNECTION=sync`. Redis service already present in Sail's `compose.yaml`.
- [x] **2.2** — Updated `SendTicketEmailJob` with `$this->onQueue('tickets')` in constructor. Updated `SendBulkEmailJob` with `$this->onQueue('emails')` in constructor.
- [x] **2.3** — Added Horizon dashboard link (`/horizon`) in the Platform Administration sidebar section, behind the `$isSuperAdmin` guard, with an SVG queue icon and "Queue Monitor" label.

## QA Results

| Check | Result |
|-------|--------|
| Pint | ✅ Pass (7 files checked) |
| PHPStan | ✅ Pass (no errors) |
| Tests | ✅ 751 passed (2008 assertions) |

## Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `composer.json` | Modified | Added `"laravel/horizon": "^5.47"` |
| `composer.lock` | Modified | Locked horizon v5.47.2 + sentinel v1.1.0 |
| `app/Providers/HorizonServiceProvider.php` | Modified | Replaced default email gate with Spatie role check (`super_admin`/`platform_admin`) |
| `bootstrap/providers.php` | Modified | Auto-registered `HorizonServiceProvider::class` by `horizon:install` |
| `config/horizon.php` | Created | Horizon config with `tickets`/`emails`/`default` queues, balance:auto, 3:2:1 weight |
| `config/queue.php` | Modified | Default queue connection changed from `database` to `redis` |
| `routes/console.php` | Modified | Added `horizon:snapshot` every 5 minutes |
| `.env.example` | Modified | `QUEUE_CONNECTION` changed from `database` to `redis` |
| `app/Jobs/Payments/SendTicketEmailJob.php` | Modified | Added `$this->onQueue('tickets')` in constructor |
| `app/Jobs/Notifications/SendBulkEmailJob.php` | Modified | Added `$this->onQueue('emails')` in constructor |
| `resources/views/components/navigation/sidebar.blade.php` | Modified | Added Horizon link in Platform Administration section |

## Deviations from Design

None — implementation matches design.

## Remaining Tasks (Phase 3 & 4)

- [ ] Phase 3: Testing (RED/GREEN/REFACTOR)
- [ ] Phase 4: Docs / Retro Sync

## Status

6/6 tasks complete (Phases 1 & 2). Ready for next batch (Phase 3) or verify.
