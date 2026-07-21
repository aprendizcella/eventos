## Exploration: Sprint 6.2 compliance and security scope

### Current State

The roadmap groups three pending capabilities under Sprint 6.2: an audit-log UI, GDPR personal-data export/anonymization, and TOTP MFA. The repository already has useful foundations, but none of the three user-facing capabilities is complete.

- **Audit foundation exists.** The `activity_log` table is installed with polymorphic subject and causer, event, JSON attribute changes, JSON properties, and timestamps. `User`, `Organizer`, events, orders, payments, attendees, notifications, settings, and other domain models use Spatie Activitylog. Explicit authentication events are recorded by `RecordAuthActivityAction`, with an allowlist that excludes credentials and tokens. The existing auth tests verify registration, login, logout, password-reset events, privacy filtering, and failure isolation. There is no audit-log query service, admin page, filtering policy, retention UI, or documented tenant-aware activity projection.
- **Admin and account UI exist.** Admin is implemented with Livewire Volt pages for dashboard, users, events, settings, and reports. The account area currently supports profile and password changes through thin controllers and Actions. There is no account privacy/export/anonymization page and no MFA page.
- **Exports and async processing are reusable but domain-specific.** `ExportAttendeesAction` provides safe streamed CSV generation with `lazyById`; report ViewModels provide CSV exports. Notification delivery uses queued jobs, Horizon, retry/backoff, and explicit tenant-awareness decisions. These patterns can inform a GDPR export job, but they do not constitute a personal-data inventory or secure export-delivery mechanism.
- **Authorization and tenancy are established but require explicit composition.** Spatie Permission teams use `organizer_id`; organizer routes use `organizer.detect`; global administration has an `EnsureGlobalAdminContext` middleware that switches the permission team to `0` and restores it. The middleware is aliased in `bootstrap/app.php`, but the current admin route group visibly applies only the role middleware, so the Sprint 6.2 design must verify and enforce the global context rather than assume the alias is active. Activity rows have no tenant column, so tenant visibility must be derived from the subject/causer and the current organizer relationship, with global records handled separately.
- **GDPR and MFA storage are absent.** The `users` table has no `deleted_at`, anonymization marker, MFA secret, confirmation timestamp, recovery-code storage, or device/session model. Sanctum tokens and web sessions exist, and suspension revokes both, but this is not MFA. No MFA package, TOTP implementation, recovery flow, or MFA-specific rate limiter is present. Several tenant-owned records contain personal data (orders, attendees, waitlist entries, notification recipient logs, payments, invoices, and activity properties), and retention/legal-hold rules are not yet specified.

Laravel Boost confirms the installed versions are Laravel 12.62, PHP 8.4, Livewire 4.3.1, Volt 1.10.5, Sanctum 4.3.2, Horizon 5.47.2, Pest 4.7, and Larastan 3.9.6. Version-specific guidance relevant to the eventual design includes Livewire 4 `streamDownload` behavior and download assertions, Laravel `response()->streamDownload`, encrypted queued jobs through `ShouldBeEncrypted`, Laravel route rate limiting, and Sanctum token revocation. These support implementation choices but do not supply MFA or GDPR semantics.

### Affected Areas

- `app/Models/User.php`, `database/migrations/0001_01_01_000000_create_users_table.php` — account privacy fields, anonymization behavior, and MFA state are currently missing; password and token exclusions must remain intact.
- `app/Models/Organizer.php`, `config/permission.php`, `app/Http/Middleware/EnsureGlobalAdminContext.php`, `app/Http/Middleware/DetectCurrentOrganizer.php`, `app/Support/Multitenancy/OrganizerTenantFinder.php` — define team `0` global-admin isolation, tenant resolution, and cross-organizer boundaries.
- `database/migrations/2026_06_23_211907_create_activity_log_table.php`, `config/activitylog.php`, `app/Actions/Auth/RecordAuthActivityAction.php`, activity-enabled models/actions — reusable audit persistence, but no immutable-read model, tenant discriminator, retention policy, or UI projection.
- `routes/web.php`, `bootstrap/app.php` — account/admin/MFA routes and middleware composition; verify whether `global.admin` must be added to the admin group.
- `resources/views/livewire/admin/*.blade.php`, `resources/views/account/*.blade.php`, `resources/views/components/navigation/*.blade.php` — existing Volt/admin/account presentation patterns and navigation extension points.
- `app/Actions/Account/*`, `app/Actions/Admin/Users/*`, `app/Http/Controllers/Account/*` — write-side Action and FormRequest patterns for privacy requests, anonymization, and MFA operations.
- `app/Actions/Attendees/ExportAttendeesAction.php`, `app/ViewModels/Organizers/*ReportsViewModel.php`, `app/Support/Reports/CsvHelper.php` — streaming/chunked export and CSV safety patterns, not sufficient alone for GDPR export.
- `app/Jobs/Notifications/SendBulkEmailJob.php`, `config/multitenancy.php`, `config/queue.php`, `config/horizon.php` — queue, retry, tenant-awareness, and operational patterns for asynchronous export/anonymization.
- `tests/Feature/Audit/*`, `tests/Feature/Account/*`, `tests/Feature/Organizers/*`, `tests/Feature/AdminAuthorizationTest.php` — existing auth-audit, account, tenancy, team-0, and authorization regression coverage to extend.
- `docs/01-producto/PLAN_IMPLEMENTACION.md`, `docs/02-arquitectura/04-admin-platform.md`, archived Sprint 1.1 auth-audit spec — roadmap and constraints; Sprint 6.1 lifecycle caveats are evidence notes, not 6.2 requirements.

### Approaches

1. **Keep one Sprint 6.2 change** — implement audit UI, GDPR workflows, and MFA in one proposal/design/tasks set.
   - Pros: one roadmap item, one integrated security review, shared account/admin navigation and audit decisions can be coordinated.
   - Cons: three different threat models and data lifecycles; GDPR policy decisions can block implementation; MFA changes authentication boundaries; likely exceeds the 800-line review budget and makes rollback/verification less isolated.
   - Effort: High

2. **Split into 6.2a audit foundation/UI, 6.2b GDPR, and 6.2c MFA** — sequence the changes, allowing each to have independent specs and verification.
   - Pros: isolates the highest-risk privacy and authentication work; audit foundations can define event visibility/retention before GDPR and MFA depend on them; each change has a clear rollback boundary and smaller review surface.
   - Cons: requires three proposals/designs and coordination of shared navigation/permissions; GDPR may need a small data-inventory decision before 6.2a is complete.
   - Effort: Medium overall, High across the three changes

### Recommendation

Split the roadmap item into **6.2a Audit visibility and audit policy**, **6.2b GDPR data rights**, and **6.2c MFA/TOTP**. Do not start proposal/specification until the product answers below are recorded. Sequence 6.2a first because it can establish the audit read model, visibility permissions, retention/immutability rules, and security-event vocabulary. Run 6.2b next because anonymization and export require an agreed data inventory, retention exceptions, and treatment of audit evidence. Run 6.2c after the authentication policy is explicit; it can reuse existing login/logout/password-reset Actions, notifications, sessions, Sanctum token revocation, and rate-limiter conventions without coupling its storage migration to GDPR.

Rough authored changed-line risk forecasts (implementation plus tests/docs, not exact estimates):

| Slice | Forecast | Main drivers |
|---|---:|---|
| 6.2a | 250–450 lines; Low–Medium risk | Volt admin page, query/read model, filters/pagination, policies, navigation, retention and authorization tests |
| 6.2b | 450–700 lines; Medium–High risk | data inventory, export format/job/storage/download, anonymization transaction, retention exceptions, tenant/global tests |
| 6.2c | 400–650 lines; High security risk | secret encryption, TOTP setup/verification, recovery/revocation, login challenge, throttling, notifications and authentication tests |
| One combined 6.2 | 1,100–1,800 lines; High review risk | cross-cutting migrations, UI, security tests, and policy decisions |

The forecasts are deliberately ranges. They are sufficient to show that a single change would exceed the requested 800-line review threshold, while each slice can remain reviewable under that threshold if scope is held.

Product and security decisions required before proposal/specification:

- Which actors may see audit records: only `super_admin`, `platform_admin`, organizer owners, organizer staff, or affected users? Which event classes and fields are visible to each actor?
- Is the audit log append-only for application users, and who may perform retention cleanup? What is the retention period, legal-hold behavior, timezone, and treatment of anonymized subjects?
- What exact personal-data inventory is exported: user profile, organizer memberships/roles, orders, attendees, waitlists, notifications, payments/invoices, sessions/tokens, and audit records? JSON, ZIP of JSON/CSV, or another format? What is the delivery/expiry policy?
- Is anonymization self-service, admin-mediated, or request/approval based? Which identifiers are replaced, which financial/legal records must remain, how are foreign keys and email uniqueness preserved, and does anonymization also revoke sessions/tokens and pending jobs?
- Must user deletion be soft deletion, anonymization without deletion, or both? What happens to users who own organizers or are the last organizer administrator?
- Is MFA mandatory for global admins, organizers, selected roles, or opt-in? TOTP only or additional channels? What are setup confirmation, recovery codes, lost-device recovery, disabling/re-enrollment, remembered devices, API-token behavior, and required audit events?
- What are the rate limits and lockout semantics for MFA setup, code verification, recovery codes, export requests, and anonymization requests? Which notifications are required and must they be queued/encrypted?
- Does MFA apply to web sessions, Sanctum tokens, or both? Must existing sessions/tokens be revoked after enable/disable, password change, anonymization, or suspicious activity?
- Should exports be synchronous for small accounts and queued for large accounts, and where are temporary files stored and deleted? Is a signed, single-use download required?

### Risks

- `activity_log` is global and polymorphic without `organizer_id`; a naive admin query can leak one organizer's records to another. A dedicated visibility policy/query must be mandatory, with explicit global-admin context and tenant resolution tests.
- Team `0` is security-sensitive. The admin group currently does not visibly include `global.admin`; adding or relying on it must be verified against route behavior and permission-cache/session restoration tests.
- Anonymization can destroy evidence needed for financial, fraud, ticketing, or audit obligations. It must use a field-level policy and transaction boundaries rather than broad model deletion.
- Activity properties and custom answers are JSON blobs that may contain personal or secret data despite model-level allowlists. Export and anonymization must inspect every producer and define redaction rules; audit data must not become a privacy bypass.
- Queued exports can outlive a user request or tenant context. Jobs must carry only safe identifiers, use explicit tenant/global semantics, have expiry/cleanup, and avoid putting raw personal data in queue payloads; Laravel supports encrypted jobs but encryption does not replace authorization.
- MFA changes login and recovery flows and can lock users out if recovery and last-admin rules are underspecified. It requires adversarial testing, throttling, audit events, and a documented operational recovery path.
- Existing authorization uses both role checks and policies. New capabilities should prefer permission-based checks where possible and must not broaden `platform_admin` access to tenant data accidentally.

### Ready for Proposal

**No, not yet for a single combined proposal.** The technical direction is ready for three scoped proposals, but the product owner must first decide the data inventory/retention/anonymization policy and MFA enrollment/recovery policy. After those decisions, start with `sprint-6-2a-audit-visibility`, then `sprint-6-2b-gdpr-data-rights`, and finally `sprint-6-2c-mfa-totp`. The current exploration is intentionally read-only; no application code was changed.
