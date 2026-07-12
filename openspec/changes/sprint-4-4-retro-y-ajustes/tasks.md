# Tasks: Sprint 4.4 — Queue Observability & Retro

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 450-650 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 infra → PR 2 jobs/sidebar/docs |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Horizon/Redis foundation | PR 1 | Base PR for provider, config, console schedule |
| 2 | Job routing + UI/docs | PR 2 | Depends on PR 1; keep review diff focused |

## Phase 1: Foundation

- [x] 1.1 Add `app/Providers/HorizonServiceProvider.php` and register it in `bootstrap/providers.php` with `Horizon::auth()` role gate.
- [x] 1.2 Create/update `config/horizon.php`, `config/queue.php`, and `routes/console.php` for Redis defaults and `horizon:snapshot` scheduling.

## Phase 2: Core Implementation

- [x] 2.1 Wire Horizon + Redis runtime details in `composer.json` / Sail-related config and confirm test env stays `sync`.
- [x] 2.2 Update `app/Jobs/Payments/SendTicketEmailJob.php` and `app/Jobs/Notifications/SendBulkEmailJob.php` to dispatch on `tickets` and `emails` queues.
- [x] 2.3 Add the Horizon entry to `resources/views/components/navigation/sidebar.blade.php` behind the admin guard.

## Phase 3: Testing (RED/GREEN/REFACTOR)

- [ ] 3.1 RED: add tests for Horizon authorization, queue selection, and sidebar visibility in `tests/Feature`.
- [ ] 3.2 GREEN: make the provider, queue config, jobs, and navigation pass the new assertions.
- [ ] 3.3 REFACTOR: keep queue names explicit and align provider/bootstrap wiring with Laravel 12 conventions.

## Phase 4: Docs / Retro Sync

- [ ] 4.1 Update `docs/00-estado/RETRO_FASE_4.md`, `docs/00-estado/ESTADO_EJECUCION.md`, and `docs/01-producto/PLAN_IMPLEMENTACION.md`.
- [ ] 4.2 Verify the change against the `queue-observability` spec scenarios and note any follow-up for the orchestrator.
