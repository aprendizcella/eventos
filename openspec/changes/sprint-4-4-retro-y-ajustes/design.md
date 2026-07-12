# Design: Sprint 4.4 — Queue Observability & Retro

## Technical Approach

Install Laravel Horizon as a real-time queue monitoring dashboard backed by Redis. Segment existing jobs into named queues (`tickets`, `emails`, `default`) by priority. Protect Horizon behind `super_admin`/`platform_admin` gate via a dedicated provider. Add a conditional sidebar link for admins. Testing stays on `QUEUE_CONNECTION=sync` (already set in `phpunit.xml`).

## Architecture Decisions

### Decision: Dedicated `HorizonServiceProvider` for gate/auth

**Choice**: New `App\Providers\HorizonServiceProvider` registered in `bootstrap/providers.php`
**Alternatives**: Gate in `AppServiceProvider::boot()`, inline in `routes/web.php`
**Rationale**: Horizon gate config is infrastructure — keeping it in a dedicated provider avoids bloating `AppServiceProvider` and follows Laravel 12's provider-per-concern convention. The `Horizon::auth()` closure checks the user's role via Spatie's `hasRole()`.

### Decision: Redis as operational queue backend

**Choice**: Redis for non-test environments via `QUEUE_CONNECTION=redis`
**Alternatives**: Keep `database` driver (no real-time monitoring), use Horizon with DB driver (unsupported)
**Rationale**: Horizon requires Redis. The Sail docker compose already has a `redis` service (per `compose.yaml`); no new infrastructure. Testing remains deterministic via `QUEUE_CONNECTION=sync` in `phpunit.xml`.

### Decision: Named queues via `config/horizon.php` workloads

**Choice**: Define a single `default` Horizon supervisor balancing `tickets` (high), `emails` (medium), `default` (low)
**Alternatives**: Multiple supervisors per queue, separate Horizon environments
**Rationale**: A single supervisor with weighted `balance:auto` is sufficient for MVP — no need for dedicated worker processes yet. Jobs explicitly declare their queue via `->onQueue()`.

### Decision: Minimal backoffice entry point (sidebar link only)

**Choice**: Conditional Horizon link in the existing "Platform Administration" sidebar section, visible only to `super_admin`
**Alternatives**: Full admin dashboard, dedicated route group with middleware
**Rationale**: The spec explicitly says "no new product dashboard." The existing sidebar already has a `@if ($isSuperAdmin)` block (line 135). Adding the Horizon link there is a one-line change consistent with the existing pattern for Platform Reports.

## Data Flow

```
Request ──→ HorizonServiceProvider::gate() ──→ Route '/horizon' (Horizon built-in routes)
                     │
                     ├── hasRole('super_admin') ──→ GRANT
                     ├── hasRole('platform_admin') ──→ GRANT
                     └── otherwise ──→ DENY (403)

Job dispatch ──→ SendTicketEmailJob::onQueue('tickets') ──→ Redis[tickets]
              ──→ SendBulkEmailJob::onQueue('emails')   ──→ Redis[emails]
              ──→ Other jobs (no onQueue)               ──→ Redis[default]

Horizon supervisor polls Redis → displays metrics in dashboard
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add `"laravel/horizon": "^5.30"` to `require` |
| `config/horizon.php` | Create | Horizon config with `default` environment, `tickets`/`emails`/`default` queue weights, `balance:auto` |
| `config/queue.php` | Modify | Set `default` to `env('QUEUE_CONNECTION', 'redis')` |
| `app/Providers/HorizonServiceProvider.php` | Create | Register `Horizon::auth()` gate checking `super_admin` or `platform_admin` role |
| `bootstrap/providers.php` | Modify | Append `App\Providers\HorizonServiceProvider::class` |
| `app/Jobs/Payments/SendTicketEmailJob.php` | Modify | Add `->onQueue('tickets')` in constructor or `__construct` call |
| `app/Jobs/Notifications/SendBulkEmailJob.php` | Modify | Add `->onQueue('emails')` in constructor or `__construct` call |
| `.env.example` | Modify | Document `QUEUE_CONNECTION=redis` for local/Sail use |
| `compose.yaml` | Modify | Ensure Redis service is available to Horizon |
| `resources/views/components/navigation/sidebar.blade.php` | Modify | Add Horizon link in Platform Administration section behind `$isSuperAdmin` guard |
| `routes/console.php` | Modify | Add `Schedule::command('horizon:snapshot')->everyFiveMinutes()` for metrics |
| `docs/00-estado/RETRO_FASE_4.md` | Create | Sprint 4 retrospective |
| `docs/00-estado/ESTADO_EJECUCION.md` | Modify | Sync completion status |
| `docs/00-estado/PLAN_IMPLEMENTACION.md` | Modify | Sync roadmap |

## Interfaces / Contracts

No new interfaces. Contracts remain unchanged:
- `ShouldQueue` already implemented on both jobs
- `->onQueue(string)` is a native Laravel `Queueable` method
- Horizon gate uses `Closure` signature: `fn (User $user): bool`

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Horizon gate logic | Test `Horizon::auth()` closure returns true for `super_admin`/`platform_admin`, false otherwise |
| Feature | Jobs use correct queues | Assert `$job->queue === 'tickets'` / `'emails'` after construction |
| Feature | Existing job behavior unchanged | Existing tests at `tests/Feature/Payments/SendTicketEmailJobTest.php` continue passing with `sync` driver |
| Integration | Sidebar link visibility | View test: assert Horizon link present for `super_admin`, absent for regular user |

No E2E needed — Horizon dashboard testing is out of scope for MVP.

## Migration / Rollout

1. `composer require laravel/horizon` → publish config/assets
2. Update `.env.example` and local Sail environment settings with `QUEUE_CONNECTION=redis`
3. Deploy config changes; Horizon dashboard is live once workers run via Redis
4. No data migration — existing jobs on `database` driver complete naturally; new jobs use Redis
5. Queue backlog can be drained on the old connection before switching by running a maintenance command if needed, but the spec allows gradual switch: Horizon+Redis handles new dispatch, database worker drains old

## Open Questions

- [ ] Confirm `platform_admin` role exists in the current Spatie Permission seed or needs creation
- [ ] Decide whether to add a `HorizonMiddleware` protecting the full route group or rely solely on `Horizon::auth()` (the gate approach is sufficient per Horizon docs)
- [ ] Verify Redis service name in Sail's `compose.yaml` resolves as `redis` host from the app container
