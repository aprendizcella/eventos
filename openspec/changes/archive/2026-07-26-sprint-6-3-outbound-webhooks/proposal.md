# Proposal: Sprint 6.3 Contract-First Outbound Webhooks

## Intent

Provide organizers a secure, reliable outbound webhook foundation, proven by one `payment.completed` vertical slice, without coupling it to pending compliance work or undocumented tooling.

## Scope

### In Scope
- Organizer-owned webhook create, list, and disable API under `/api/v1`; management UI is deferred.
- `webhook` and `webhook_delivery` persistence, signed delivery, durable outcomes, and bounded retries for `payment.completed`.
- A versioned `v1` JSON envelope containing event name, delivery ID, occurred-at timestamp, organizer ID, and an allowlisted payment/order reference payload.
- Threat-model and contract tests for authorization, tenant queues, signing, retry, and redaction.

### Out of Scope
- Other outbound event types, organizer UI, OpenAPI generation/platform selection, and README/contribution/architecture documentation expansion.
- GDPR/MFA, active capture-schema work, inbound Stripe webhooks, CI/CD, monitoring, load testing, and new packages or external documentation platforms.

## Capabilities

### New Capabilities
- `outbound-webhooks`: Organizer-scoped outbound webhook subscriptions and at-least-once delivery of the `payment.completed` v1 contract.

### Modified Capabilities
- None. Existing `tenant-aware-jobs` requirements remain authoritative and are satisfied by this implementation.

## Approach

- Allowlist only `payment.completed`; dispatch only after the payment transaction commits. Deliveries are at-least-once: consumers deduplicate on stable `delivery_id`.
- Require an `https` public hostname on port 443. Resolve DNS on every attempt; reject loopback, private, link-local, multicast, reserved, and non-public IPv4/IPv6 results; disable redirects. Tests use an HTTP fake, never a private destination.
- Generate a cryptographically random signing secret, encrypt it at rest, reveal it once at creation or rotation, and invalidate the previous secret immediately. Sign the canonical raw envelope using HMAC-SHA-256 in `X-HiEvents-Signature: v1=<hex>`; include event and delivery-ID headers.
- Queue only identifiers plus organizer context; the worker restores tenant context and re-scopes subscription/delivery reads to that organizer. Never serialize secrets or raw payloads in jobs.
- Treat 2xx as success; retry network failures, 429, and 5xx for five total attempts (initial plus 1, 5, 30, and 120-minute delays). Other 4xx responses fail terminally.
- Encrypt the minimal canonical envelope needed for retries. Store terminal status, attempt count, timestamps, and redacted diagnostic metadata; never retain request/response bodies, authorization data, or secrets. Purge delivery records after 30 days.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `database/migrations/`, `app/Models/`, `app/Enums/` | New | Subscriptions and redacted delivery audit state. |
| `app/Actions/Webhooks/`, `app/Services/Webhooks/`, `app/Jobs/Webhooks/` | New | Contract construction, dispatch, tenant-safe delivery, retry. |
| `app/Http/{Controllers,Requests,Resources}/`, `routes/api.php` | Modified | Organizer-scoped v1 subscription API. |
| `app/Events/Payments/`, `tests/Feature/Webhooks/` | Modified/New | After-commit mapping and focused contract coverage. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| SSRF/DNS rebinding | Medium | Public-IP validation per attempt; HTTPS-only; no redirects. |
| Duplicate or cross-tenant delivery | Medium | Stable IDs, after-commit dispatch, explicit tenant re-scoping. |
| Secret/diagnostic leakage | Medium | Encryption, one-time reveal, redaction, 30-day purge. |

## Rollback Plan

Disable subscriptions to stop new dispatches, pause the webhook queue, and retain redacted records for diagnosis. Revert the API/dispatcher; migration rollback removes only webhook tables after operations confirms no required evidence remains.

## Dependencies

- Existing Redis/Horizon and `tenant-aware-jobs` behavior. No package or documentation-platform approval is assumed.

## Success Criteria

- [ ] Only an owning organizer can manage or deliver through its subscriptions; queued work cannot cross tenants.
- [ ] A committed payment produces one signed v1 delivery record and queued attempt; a rolled-back payment produces none.
- [ ] Signature, URL-policy, retry schedule, terminal-state, redaction, and retention contracts pass focused Pest tests.
- [ ] First slice remains estimated at 700–1,100 authored lines, within the 5,000-line solo-maintainer review budget.
