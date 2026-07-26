# Design: Sprint 6.3 Contract-First Outbound Webhooks

## Technical Approach

Add one organizer-owned, at-least-once outbound `payment.completed` v1 slice. A listener registered beside the existing `PaymentCompleted` listeners creates durable deliveries after the enclosing Stripe transaction commits; a tenant-aware job sends each encrypted canonical envelope. This supplements, rather than changes, the inbound Stripe boundary at `routes/api.php` and `app/Actions/Payments/HandleStripeWebhookAction.php`.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| External contract | Allowlist only `payment.completed`; stable UUID `delivery_id`; JSON v1 envelope and `X-HiEvents-Signature: v1=<hex>` | Serialize Laravel events; sign a re-encoded payload | Consumers need a versioned, deduplicable contract. HMAC-SHA-256 covers the exact canonical raw bytes stored and sent, avoiding serialization drift. |
| Secrets and API boundary | `Webhook` stores its generated signing secret with Laravel's `encrypted` cast; creation and rotation return the replacement once through dedicated responses, while `WebhookResource` never includes it | Plaintext/hash-only secret; expose in normal resource | Encryption permits later signing; hash-only cannot. Immediate replacement invalidates the prior secret and one-time reveal limits disclosure. |
| Destination safety | Creation validates HTTPS, port 443, hostname (no credentials/IP literals); every job attempt resolves DNS and accepts only public IPv4/IPv6 results, with redirects disabled and explicit connect/request timeouts | `url` validation only; validate DNS once | This prevents SSRF and DNS rebinding. The job fails terminally without requesting an unsafe target. |
| Async ownership | Job receives only `organizerId` and `webhookDeliveryId`; tenant-aware queue restoration plus `where('organizer_id', ...)` queries | Serialize models/payload/secret; rely only on current tenant | `config/multitenancy.php` already restores the tenant, but explicit re-scoping enforces the `tenant-aware-jobs` no-leak contract and keeps sensitive data out of queue payloads. |
| Delivery lifecycle | `Pending` → `Delivering` → `Succeeded` or `Failed`; five total attempts with 1, 5, 30, 120-minute backoffs | Infinite retry; retry all 4xx | 2xx succeeds; connection failures, 429, and 5xx throw/release for retry. Other 4xx and unsafe URLs are terminal. |

## Data Flow

    Stripe raw request → StripeWebhookController → HandleStripeWebhookAction (DB transaction)
                                                        │
                                     PaymentCompleted → DispatchPaymentCompletedWebhooksListener
                                                        │ afterCommit
                         webhook_delivery rows → DeliverWebhookJob(organizerId, deliveryId)
                                                        │ restore organizer + re-scope + DNS check
                                                        └→ signed HTTPS POST → redacted outcome

`PaymentCompleted` currently fires inside `HandleStripeWebhookAction`'s transaction. The new listener MUST register an `afterCommit` callback before creating delivery records/dispatching jobs, so a rollback creates no delivery. It resolves `Payment → ticketOrder → event → organizer` and emits only the allowlisted `payment_id`, `order_id`, and timestamps—never attendee/customer data, monetary/provider fields, or Stripe secrets.

## File Changes

| File | Action | Description |
|---|---|---|
| `database/migrations/*_create_webhook_table.php` | Create | Singular organizer-scoped subscription table: URL, encrypted secret, enabled state, timestamps, soft delete, and organizer/state indexes. |
| `database/migrations/*_create_webhook_delivery_table.php` | Create | Delivery UUID, organizer/webhook/payment references, event/version, encrypted canonical envelope, state, attempts, timestamps, redacted error/status metadata, expiry index. |
| `app/Models/{Webhook,WebhookDelivery}.php`, `database/factories/{Webhook,WebhookDelivery}Factory.php`, `app/Enums/WebhookDeliveryStatus.php` | Create | Fillable/casts/relations and explicit lifecycle values; add organizer relations and test factories. |
| `app/Actions/Webhooks/*`, `app/Services/Webhooks/{WebhookEnvelopeFactory,WebhookDestinationPolicy,WebhookDispatcher}.php` | Create | Subscription lifecycle, immutable envelope/signature, DNS policy, and delivery orchestration. |
| `app/Listeners/Payments/DispatchPaymentCompletedWebhooksListener.php`, `app/Jobs/Webhooks/DeliverWebhookJob.php` | Create | After-commit fan-out and tenant-rescoped HTTP attempt with `$tries = 5`, backoff, and `failed()` terminal recording. |
| `app/Http/{Controllers,Requests,Resources}/Webhooks/*`, `app/DataTransferObjects/Webhooks/*`, `app/Policies/WebhookPolicy.php`, `routes/api.php` | Create/Modify | Sanctum + `organizer.detect` nested `/api/v1/organizers/{organizer}/webhooks` create/list/disable/rotate API; controller authorizes and delegates DTOs/actions. |
| `app/Providers/AppServiceProvider.php` | Modify | Register the dedicated payment-completed listener. |
| `app/Console/Commands/Webhooks/PurgeExpiredWebhookDeliveriesCommand.php`, `routes/console.php` | Create/Modify | Daily removal of delivery records 30 days after terminal completion; no request/response bodies are ever retained. |
| `tests/Feature/Webhooks/*` | Create | Contract, API, queue, HTTP-fake, isolation, and retention coverage. |

## Interfaces / Contracts

```json
{
  "version": "v1",
  "event": "payment.completed",
  "delivery_id": "uuid",
  "occurred_at": "ISO-8601 UTC",
  "organizer_id": 123,
  "data": {"payment_id": 1, "order_id": 42}
}
```

The POST body is the encrypted-at-rest canonical JSON string. Headers are `Content-Type: application/json`, `X-HiEvents-Event`, `X-HiEvents-Delivery-Id`, and the HMAC header. Resources expose endpoint, enabled state, identifiers, and timestamps only. Disable is idempotent and stops future fan-out; rotation immediately replaces the usable secret; in-flight jobs re-check enabled state.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Unit | canonical bytes/HMAC; URL/DNS classifications; retryable status classification | Red tests first for deterministic factory/policy inputs. |
| Feature | organizer authorization, one-time secret redaction, after-commit/rollback, tenant re-scope, durable states, purge | `LazilyRefreshDatabase`, Sanctum, Queue/Event fakes, two-organizer fixtures. |
| Job/HTTP | signed raw body/headers, 2xx, 4xx terminal, 429/5xx/connection retry schedule, redirect refusal | `Http::preventStrayRequests()` and `Http::fake()`; fake queue interactions to assert releases. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Outbound HTTP is covered by the destination-security contract above, not shell/process integration.

## Migration / Rollout

Deploy additive tables before routes/listener. Existing payments produce no delivery without an enabled subscription. Rollback disables subscriptions and pauses the webhook queue; retain only redacted terminal metadata until the 30-day purge. No existing rows require backfill.

## Open Questions

- [ ] None; additional event types and an organizer UI remain follow-on work.
