## Exploration: Sprint 6.3 Outbound Webhooks and Documentation

### Current State

Sprint 6.3 is the pending Phase 6 integration/documentation sprint. The roadmap explicitly requires organizer-configurable outbound webhooks, HMAC signing, exponential-backoff retries, complete API documentation, development documentation, and passing QA. Its sole documented dependency is Sprint 6.1, which is implemented and archived.

The repository already has useful adjacent foundations:

- Inbound Stripe webhooks use a raw payload, signature verification, transaction-backed idempotency, and a dedicated controller/action boundary.
- Laravel domain events exist for payments, tickets, and waitlist changes; Redis-backed queues, Horizon access, and tenant-aware job requirements are established.
- The API is versioned under `/api/v1`, uses Sanctum and organizer/global-admin middleware, but has no OpenAPI artifact or outbound-webhook persistence/runtime.

There are no `webhook` or `webhook_delivery` migrations, subscription model, dispatcher, delivery job, secret lifecycle, organizer webhook UI, or API documentation source of truth.

### Documented Objectives and Requirements

#### Functional requirements

The product roadmap requires Sprint 6.3 to deliver:

1. Organizer-owned webhook configuration.
2. `webhook` and `webhook_delivery` persistence.
3. A `WebhookDispatcher` that sends signed outbound webhooks.
4. Retry handling with exponential backoff.
5. Complete OpenAPI/Swagger API documentation.
6. Development documentation covering README, contribution guidance, and architecture.
7. Automated tests for webhook sending, signatures, and retries.

#### Non-functional requirements

- Organizer isolation MUST also hold for queued delivery work; a delivery cannot read or mutate another organizer's subscription or data.
- Secrets MUST not be exposed in UI, API responses, activity data, exception messages, queue payloads, or documentation examples.
- Delivery processing MUST be asynchronous, operationally observable, and compatible with the established Redis/Horizon queue model.
- Retries MUST be bounded and deterministic enough to test; delivery records need an auditable terminal state.
- New API contracts MUST remain versioned under the established `/api/v1` convention and use existing Sanctum, authorization, Resource/ViewModel, and rate-limit patterns where applicable.
- Implementation MUST retain strict TDD and the configured quality gates: Pest, Pint, PHPStan level 8, Rector, SonarQube, and 80% coverage for new files.

### Source of Truth and Contradictions

| Source | Authority for this exploration | Conflict / resolution |
|---|---|---|
| `docs/01-producto/PLAN_IMPLEMENTACION.md` | Primary product scope, acceptance criteria, and explicit dependency: Sprint 6.1. | It labels 6.3 pending/deferred and does not define event types, payload versioning, secret rotation, retry limits, or documentation generation strategy. These are proposal decisions, not assumed requirements. |
| `openspec/config.yaml` and repository `AGENTS.md` | Delivery constraints: strict TDD, Laravel architecture, Sail-based QA, no new packages without approval. | The plan's generic `composer qa` commands must be reconciled with the repository's Sail commands at task time. |
| `docs/00-estado/ESTADO_EJECUCION.md` | Current implementation status. | It says 6.2a/6.2b are archived, but also lists GDPR/MFA as pending. It does not make them dependencies of 6.3. |
| `openspec/changes/sprint-6-2-compliance-security/exploration.md` | Evidence that GDPR and MFA remain separate, unimplemented security/privacy work. | It recommended separate 6.2b/6.2c changes and expressly does not make them prerequisites for webhooks. |
| `openspec/changes/sprint-6-2a-capture-schema/*` | Evidence that capture-schema implementation and verification completed but its change remains active/unarchived. | This is OpenSpec lifecycle debt, not a documented technical dependency of outbound delivery. Its classification columns may be useful later for auditability but Sprint 6.3 must not couple itself to that unclosed change. |
| `routes/api.php`, Stripe webhook action/controller, queue specs | Current integration patterns. | They implement inbound provider webhooks, not organizer-facing outbound subscriptions; code must not be repurposed as an outbound contract without a dedicated design. |

**Dependency decision:** unfinished GDPR/MFA do **not** block Sprint 6.3. They are explicitly separate future work, and the 6.3 roadmap names only Sprint 6.1 as a dependency. The unarchived capture-schema change also does **not** block implementation because its verified classification schema is unrelated to subscription ownership, delivery signing, or retries. It is a process risk: do not modify, supersede, or rely on unpublished delta specifications from that active change. Sprint 6.3 should define its own organizer ownership and delivery audit model.

### Affected Areas

- `database/migrations/` — new singular `webhook` and `webhook_delivery` tables with organizer ownership, durable delivery state, indexes, and soft deletes where project conventions apply.
- `app/Models/`, `app/Enums/` — subscription and delivery aggregates plus explicit delivery/event status values.
- `app/Actions/Webhooks/`, `app/Services/Webhooks/`, `app/Jobs/Webhooks/` — subscription lifecycle, canonical payload/signature construction, dispatch orchestration, queued delivery, retry/backoff, and terminal failure handling.
- `app/Events/Payments/`, `app/Events/Tickets/`, `app/Events/Waitlist/` — event-to-outbound-event mapping; the allowlist must be explicit rather than emitting arbitrary internal events.
- `app/Http/Controllers/`, `app/Http/Requests/`, `app/DataTransferObjects/`, `app/Http/Resources/`, `app/Policies/` — organizer-scoped webhook management API boundary.
- `routes/api.php`, organizer navigation/Volt components — versioned organizer routes and a domain-specific management UI, after server-side authorization is established.
- `tests/Feature/Webhooks/`, existing payment/tenant queue tests — subscription isolation, HMAC, payload stability, dispatch, retries, idempotency, and failure-redaction coverage.
- API and development documentation locations — an OpenAPI source plus README/contribution/architecture updates, only after the documentation authority is chosen.

### Approaches

1. **One comprehensive Sprint 6.3 implementation** — schema, all event types, UI, API docs, and repository docs in one change.
   - Pros: matches the roadmap wording in a single delivery.
   - Cons: combines security-sensitive delivery infrastructure with an undefined external contract and broad documentation work; difficult to test and roll back coherently.
   - Effort: High.

2. **Contract-first, staged Sprint 6.3** — establish the outbound contract and one vertical delivery slice, then expand event coverage/UI/docs in bounded follow-on slices within the same sprint change.
   - Pros: makes payload/version/security decisions explicit, proves queue and tenant behavior early, keeps every implementation unit independently verifiable, and fits the 5,000-line solo-maintainer budget.
   - Cons: requires disciplined scope control and defers broad event coverage until the delivery foundation is proven.
   - Effort: Medium per slice; Medium-High overall.

### Bounded First Implementation Slice

Implement a **single-event outbound webhook vertical slice**:

- organizer-owned subscription and delivery persistence;
- authorized subscription create/list/disable API (UI deferred);
- one explicitly selected existing domain event, recommended `payment.completed`, dispatched after its transaction commits;
- canonical JSON envelope with an explicit event name, event ID, timestamp, organizer ID, payload version, and HMAC signature header;
- queued HTTP delivery, bounded exponential-backoff retries, and redacted delivery outcome records;
- focused Pest coverage for isolation, signature validity, retry scheduling, success, and terminal failure.

`payment.completed` is the recommended first event because the repository already emits `PaymentCompleted` after confirming an order. The proposal must still confirm its payload's allowed fields and whether the event must dispatch strictly after commit. No endpoint URL, signature header format, secret rotation policy, or retry schedule is currently documented, so none is fixed by this exploration.

### Non-Goals

- GDPR export, anonymization, deletion, retention, legal hold, or consent workflows.
- MFA/TOTP, recovery codes, login challenges, or session/token policy changes.
- Changes to `activity_log` capture, classification, backfill, or the active capture-schema artifact.
- Replacing or changing the inbound Stripe webhook endpoint.
- An unrestricted reflection of every Laravel domain event, delivery to arbitrary internal URLs without validation policy, or a generic event bus.
- Adding an OpenAPI package or external documentation platform without explicit approval.
- Deployment, CI/CD, backups, monitoring expansion, or load testing from Sprint 6.4.

### Recommendation

Use the staged, contract-first approach. Start the proposal with a written external webhook contract and threat model, then design the first vertical slice above. Follow it with: (1) expanded approved event types and organizer Volt management UI, (2) OpenAPI coverage of existing and new API endpoints, and (3) repository development documentation. Keep delivery records and redacted operational metadata in the product; do not depend on the unfinished audit/compliance work for them.

The 5,000-line review budget permits the overall sprint, but delivery should remain in small work units: foundation/one event (estimated 700–1,100 authored lines), UI and additional events (600–1,000), then API/developer documentation (500–900). No PR chain is required for the solo-maintainer workflow, but each unit needs its own tests and rollback boundary.

### Risks

- **SSRF and private-network access:** organizer-configured destinations require an explicit URL validation and outbound-network policy; a simple `url` validation rule is insufficient.
- **Secret leakage:** plaintext signing secrets, request bodies, and response bodies can leak through logs, activity records, failed jobs, or delivery UI.
- **At-least-once delivery:** retries and worker crashes can duplicate deliveries; the external contract must define a stable delivery/event identifier and consumers must be able to deduplicate.
- **Transaction ordering:** dispatching before commit can expose an event whose underlying state rolls back; the design must enforce after-commit dispatch.
- **Tenant leakage in queues:** the job must resolve the subscription and payload through explicit organizer ownership and satisfy the tenant-aware-job specification.
- **External contract drift:** without an event allowlist and versioned envelope, API/documentation changes can break webhook consumers.
- **Documentation scope ambiguity:** “complete API documentation” has no identified source or generation strategy; selecting a package would require approval.
- **Process debt confusion:** the active capture-schema change and unimplemented GDPR/MFA work can be mistaken for blockers; they are not, but their lifecycle state should remain visible in the proposal.

### Source Documents Read

- `docs/README.md`; `docs/00-estado/ESTADO_EJECUCION.md`; `docs/00-estado/RETRO_FASE_3.md`; `docs/00-estado/RETRO_FASE_4.md`
- `docs/01-producto/PLAN_IMPLEMENTACION.md`
- `docs/02-arquitectura/DECISIONES_ARQUITECTURA.md`; `ESPECIFICACION_TECNICA_BOILERPLATE_EVENTOS.md`; `MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`; `04-admin-platform.md`
- `docs/03-ux-ui/DECISIONES_UX.md`; `PLAN_UX_FOUNDATION.md`; `REFERENCIAS_UX.md`; `COMPONENTES_UI.md`; `PLAN_MEJORA_RESPONSIVE.md`
- `docs/04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md`; `docs/05-guias/stripe_local_setup.md`; `docs/06-deuda-tecnica/ANALISIS_ARQUITECTONICO_FASE3.md`
- Relevant OpenSpec history: `sprint-6-2-compliance-security`, `sprint-6-2a-capture-schema`, archived `sprint-6-2a-audit-classification-fix`, archived `sprint-6-2b-audit-ux-integration`, and tenant/queue main specs.

### Ready for Proposal

**Yes.** Proceed to `sdd-propose` for `sprint-6-3-implementation-plan`. The proposal must resolve the outbound event allowlist, payload schema/versioning, HMAC header and secret lifecycle, URL/SSRF policy, retry/terminal-failure policy, delivery retention/redaction, and OpenAPI documentation authority before specification and implementation begin.
