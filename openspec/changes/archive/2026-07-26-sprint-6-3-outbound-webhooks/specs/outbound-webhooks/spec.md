# Outbound Webhooks Specification

## Purpose

Provide isolated, signed, at-least-once `payment.completed` v1 delivery.

## Requirements

### Requirement: Organizer Subscription Isolation

Authenticated organizer owners MUST manage only their `/api/v1` subscriptions. Cross-organizer access MUST return 403 or 404 without disclosure. Disabled subscriptions MUST receive no new deliveries.

#### Scenario: Owner manages a subscription

- GIVEN an authorized organizer A user
- WHEN they create, list, or disable organizer A's subscription
- THEN only organizer A data MUST be affected

#### Scenario: Cross-organizer management is rejected

- GIVEN organizer A's user and organizer B's subscription
- WHEN the user reads or disables that subscription
- THEN the system MUST return 403 or 404 without its data

### Requirement: Versioned Payment-Completed Envelope

Only `payment.completed` MUST be emitted. After commit, one delivery per active organizer subscription MUST be created. Canonical v1 JSON MUST contain `version: v1`, `event: payment.completed`, stable `delivery_id`, UTC RFC 3339 `occurred_at`, `organizer_id`, `data.payment_id`, and `data.order_id`; it MUST NOT contain other payment, order, customer, or credential fields. Attempts MUST use the same raw envelope.

#### Scenario: Committed payment creates a delivery

- GIVEN an organizer has an active subscription
- WHEN its payment completes and commits
- THEN one specified v1 delivery MUST be recorded and queued

#### Scenario: Rolled-back payment emits nothing

- GIVEN payment completion occurs in a rolled-back transaction
- WHEN the transaction ends
- THEN no delivery or queued attempt MUST exist

### Requirement: Secret Disclosure and Signature

The system MUST generate a cryptographically random secret, encrypt it at rest, and disclose it once at creation or rotation. Rotation MUST immediately invalidate the previous secret. Attempts MUST sign the raw envelope with HMAC-SHA-256 in `X-HiEvents-Signature: v1=<hex>` and include `X-HiEvents-Event` and `X-HiEvents-Delivery-Id`. Secrets MUST NOT appear in later responses, logs, activity data, exceptions, jobs, or diagnostics.

#### Scenario: Signature and one-time disclosure

- GIVEN a created subscription and its disclosed secret
- WHEN a delivery is sent or the subscription is read later
- THEN its signature MUST validate and the later response MUST omit secrets

### Requirement: Safe Destination Policy

Destinations MUST be `https`, public-hostname, port-443 URLs and MUST resolve at creation and each attempt. Any loopback, private, link-local, multicast, reserved, or non-public IPv4/IPv6 result MUST be rejected. Redirects MUST NOT be followed. Unsafe creation MUST return 422; unsafe later resolution MUST send no request and fail terminally.

#### Scenario: Unsafe destination is blocked

- GIVEN a private, non-443, or DNS-rebound destination
- WHEN creation or delivery validation runs
- THEN creation MUST return 422, or delivery MUST make no request and fail terminally

### Requirement: Tenant-Safe, At-Least-Once Delivery

Jobs MUST contain only identifiers and organizer context, restore it, and re-scope subscription, delivery, and source-data reads; they MUST NOT serialize secrets or raw envelopes. A 2xx MUST succeed. Network failures, 429, and 5xx MUST retry five total attempts after 1, 5, 30, and 120 minutes. Other 4xx responses, missing/disabled subscriptions, unsafe destinations, and exhausted retries MUST fail terminally.

#### Scenario: Worker preserves tenant isolation

- GIVEN an organizer A job and matching organizer B records
- WHEN the worker executes
- THEN it MUST access and deliver only organizer A data

#### Scenario: Retriable failure is bounded

- GIVEN a delivery receives 503 on every attempt
- WHEN retries execute
- THEN five attempts MUST follow the stated delays and end terminally

### Requirement: Redacted Delivery Retention

The minimal retry envelope MUST be encrypted. For 30 days, the system MUST retain only status, attempt count, timestamps, and redacted diagnostics; it MUST NOT retain request/response bodies, authorization data, secrets, or unencrypted envelopes. It MUST then purge both.

#### Scenario: Terminal failure is safely retained and purged

- GIVEN a terminal delivery that reaches 30 days of age
- WHEN it is inspected before, then processed after, the retention threshold
- THEN only redacted state MUST be visible before and all retained data MUST be removed after
