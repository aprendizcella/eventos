# Global Audit Visibility Specification

## Purpose

Define a read-only global audit UI backed by the existing `activity_log.organizer_id` and `activity_log.is_global` foundation.

## Requirements

### Requirement: Exact super-admin authorization

Only authenticated users with the exact `super_admin` role MUST access the page. `platform_admin` and every other role MUST be denied, including direct component/update requests. Authorization MUST NOT depend on the current tenant context.

#### Scenario: Authorized global access
- GIVEN an authenticated user with the exact `super_admin` role
- WHEN the user requests the audit page
- THEN the page renders successfully

#### Scenario: All other access is denied
- GIVEN a guest, `platform_admin`, or any user without the exact role
- WHEN the user requests the page or a direct component update
- THEN access is denied and no audit rows are returned

### Requirement: Persisted global classification boundary

The UI MUST return only rows where `organizer_id IS NULL AND is_global = true`. Tenant rows and unclassified rows (`organizer_id IS NULL AND is_global = false`) MUST be excluded. Ownership MUST NOT be inferred from the current request tenant, and excluded classifications MUST produce redacted structured observability.

#### Scenario: Tenant-context mismatch does not broaden access
- GIVEN a super-admin request carrying an active tenant context
- WHEN the global audit query runs
- THEN the result remains based only on persisted classification and contains no tenant-owned row

#### Scenario: Classification filtering is strict
- GIVEN global, tenant, and unclassified activity rows
- WHEN the authorized page loads
- THEN only global rows are returned and excluded classifications are absent

### Requirement: Explicit safe projection

Each row MUST expose only `id`, `log_name`, `event`, `description`, subject identity, causer identity, and `created_at`. Missing identities MAY display as `Unknown`. The UI MUST escape values and MUST NOT expose `properties`, `attribute_changes`, or raw payload/secrets.

#### Scenario: Payloads are redacted
- GIVEN a global row containing sensitive properties and attribute changes
- WHEN the page renders
- THEN neither field nor any raw payload value appears in the response or UI

### Requirement: Deterministic bounded navigation

The UI MUST be read-only, latest-first (`created_at DESC`, then unique `id DESC`), and paginated with a positive bounded page size.

#### Scenario: Ordering and pagination are stable
- GIVEN global rows with equal timestamps spanning multiple pages
- WHEN a super-admin visits consecutive pages
- THEN each row appears once in deterministic latest-first order and no page exceeds the bound

### Requirement: Safe resilient behavior and observability

The UI MUST provide loading and empty states. Query failures MUST fail closed with a safe error state and no partial rows or exception details. Structured observability for denial, exclusion, and failure MUST be redacted and MUST NOT contain payloads or secrets.

#### Scenario: Empty results are explicit
- GIVEN no rows satisfy the global classification boundary
- WHEN the authorized page loads
- THEN a safe empty state is shown

#### Scenario: Query failure fails closed
- GIVEN the global audit query fails
- WHEN the authorized page loads or updates
- THEN no rows are shown, a safe error state is shown, and redacted failure observability is emitted

#### Scenario: Component reauthorization is enforced
- GIVEN an authorized component was initially rendered and the user's role is no longer `super_admin`
- WHEN the component receives an update
- THEN the update is denied and returns no audit data

## Out of Scope

- Schema changes to `activity_log`.
- Capture-seam changes.
- Historical backfill.
- Tenant audit UI.
- Unclassified audit UI.
