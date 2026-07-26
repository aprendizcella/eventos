# Tasks: Sprint 6.3 Contract-First Outbound Webhooks

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 700–1,100 |
| 400-line budget risk | High |
| Chained PRs recommended | No — solo delivery exception |
| Suggested split | Three independently reversible work units; no PRs |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Subscription foundation/API | Solo unit 1 | `vendor/bin/sail artisan test --compact tests/Feature/Webhooks/WebhookSubscriptionTest.php` | Sanctum owner/cross-tenant requests | Webhook table, API, policy |
| 2 | Committed-envelope fan-out | Solo unit 2 | `vendor/bin/sail artisan test --compact tests/Feature/Webhooks/PaymentCompletedWebhookTest.php` | Committed/rolled-back payment transaction | Listener, delivery table, registration |
| 3 | Safe delivery lifecycle | Solo unit 3 | `vendor/bin/sail artisan test --compact tests/Feature/Webhooks/DeliverWebhookJobTest.php` | HTTP fake, DNS resolver fake, scheduled purge | Job, dispatcher, purge command |

## Phase 1: Subscription Foundation

- [x] 1.1 **RED** — Add `tests/Feature/Webhooks/WebhookSubscriptionTest.php` for owner create/list/disable, cross-organizer 403/404, idempotent disable, one-time creation/rotation secret, later-resource redaction, and 422 HTTPS/public-hostname/443 validation.
- [x] 1.2 **GREEN** — Create webhook migration/model/factory/policy, Webhooks DTOs, requests, actions, resource, controller, and nested Sanctum/`organizer.detect` routes in `routes/api.php`; encrypt the secret and never expose it from `WebhookResource`.
- [x] 1.3 **REFACTOR/VERIFY** — Align fillable/casts/relations and authorization with organizer conventions; run the Unit 1 focused test.

> **Apply evidence disposition — corrective rerun 1/1: PARTIAL.** The checkboxes preserve the recorded Unit 1 source implementation, but the required Strict-TDD pre-edit safety baseline for `routes/api.php` and `app/Models/Organizer.php` was not captured before those existing files changed. A current focused test can verify the post-edit state only and cannot recreate that historical baseline. Unit 1 must not advance to verification until a maintainer resolves or explicitly accepts this irrecoverable evidence gap.

## Phase 2: Canonical Envelope and After-Commit Fan-out

- [x] 2.1 **RED** — Add `tests/Feature/Webhooks/PaymentCompletedWebhookTest.php` for exactly one active-subscription delivery after commit, none after rollback/disable, canonical allowlisted v1 bytes (UTC timestamp and stable UUID), and no customer/payment-provider fields.
- [x] 2.2 **GREEN** — Add delivery migration/model/factory/status enum, `WebhookEnvelopeFactory`, `DispatchPaymentCompletedWebhooksListener`, and `AppServiceProvider` registration; defer durable creation and identifier-only job dispatch with `afterCommit` from `PaymentCompleted`.
- [x] 2.3 **REFACTOR/VERIFY** — Make payment→order→event→organizer resolution explicit and preserve `HandleStripeWebhookAction` transaction semantics; run the Unit 2 focused test.

> **Apply evidence disposition — corrective rerun 1/1: ACCEPTED WITH LIMITATION.** The Unit 2 implementation and focused regression suite are complete, but the historical RED execution was not persisted before the pre-existing production files were found in the worktree. The 4-test, 21-assertion focused result is retained as post-change regression evidence; no RED result is fabricated. A maintainer accepts this evidence limitation for advancing to Phase 3. Strict-TDD evidence is therefore complete by implementation/regression outcome, with the historical gap explicitly recorded in `apply-progress.md`.

## Phase 3: Tenant-Safe Delivery, Retry, and Retention

- [x] 3.1 **RED** — Add `tests/Feature/Webhooks/DeliverWebhookJobTest.php` for tenant re-scoping, serialized job containing only organizer/delivery IDs, HMAC raw-body headers, 2xx success, terminal 4xx/missing/disabled, and no secret/raw-envelope/log diagnostics.
- [x] 3.2 **RED** — In the same focused suite, fake DNS/HTTP for creation-and-attempt public-IP checks, private/loopback/link-local/multicast/reserved IPv4/IPv6, rebinding, redirect refusal, 429/5xx/network retries at 1/5/30/120 minutes, exhausted fifth attempt, redaction/encryption, and 30-day purge.
- [x] 3.3 **GREEN** — Implement `WebhookDestinationPolicy`, `WebhookDispatcher`, tenant-aware `DeliverWebhookJob` (`$tries = 5`), durable redacted outcomes, `PurgeExpiredWebhookDeliveriesCommand`, and daily `routes/console.php` schedule; unsafe targets make no request and fail terminally.
- [x] 3.4 **REFACTOR/VERIFY** — Consolidate retry/terminal transitions without retaining bodies, authorization, plaintext envelopes, or secrets; run all three focused suites, then configured Pint, PHPStan, and full Pest verification.

> **Apply evidence disposition — corrective rerun 1/1: ACCEPTED WITH LIMITATION.** The Unit 3 contract suites were added against the existing job stub rather than preserved as a historical RED artifact. The implementation, focused regression coverage, QA pipeline, and SonarQube gate are complete; no historical RED result is fabricated. The maintainer accepts this evidence limitation for verification/archiving.
