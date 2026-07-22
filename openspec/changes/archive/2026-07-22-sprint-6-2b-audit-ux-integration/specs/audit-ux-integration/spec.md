# Audit UX Integration Specification

## Purpose

Provide a responsive, read-only global-audit control surface without expanding audit visibility or data exposure.

## Requirements

### Requirement: Verified upstream global boundary

The capability MUST depend on Sprint 6.2a verification of `organizer_id IS NULL AND is_global = true`. It MUST apply this boundary before filters, pagination, and counts, and MUST NOT include tenant or unclassified rows.

#### Scenario: Only verified global rows participate
- GIVEN global, tenant, and unclassified audit rows
- WHEN an authorized user filters or pages the audit log
- THEN only global rows are returned and counted

#### Scenario: Upstream prerequisite is unresolved
- GIVEN Sprint 6.2a has not verified the canonical boundary
- WHEN this capability is considered for release
- THEN its filter and count behavior MUST NOT ship

### Requirement: Protected contextual control surface

The `/admin/audit-logs` surface MUST retain exact `super_admin` authorization, including component updates, and MUST present a contextual platform-control header with an immutable, read-only cue.

#### Scenario: Super-admin accesses the surface
- GIVEN an authenticated user with the exact `super_admin` role
- WHEN the user requests the audit surface
- THEN the contextual read-only control surface renders

#### Scenario: Authorization is rechecked
- GIVEN a rendered audit component whose user no longer has `super_admin`
- WHEN the component receives an update
- THEN access is denied and no audit data is returned

### Requirement: Fixed server-side filters and reset

The system MUST accept only allowlisted `log_name`, `event`, and bounded date-range filters. It MUST apply them server-side after the global boundary, show active filters, and reset pagination on every filter mutation or reset.

#### Scenario: Allowed filters narrow results
- GIVEN eligible rows with distinct allowed values and dates
- WHEN an authorized user applies allowed filters
- THEN only matching global rows are shown with active-filter feedback

#### Scenario: Invalid filter input is safe
- GIVEN an unknown value or an invalid or unbounded date range
- WHEN the user submits the filter input
- THEN the input is rejected or safely normalized and no broader query runs

#### Scenario: Filter change resets the page
- GIVEN the user is on a later page
- WHEN any filter changes or is reset
- THEN the next result view starts at the first page

### Requirement: Filtered count and deterministic navigation

The system MUST display the total from the filtered bounded paginator. Results MUST remain latest-first by `created_at DESC`, then unique `id DESC`.

#### Scenario: Count reflects the active result set
- GIVEN a filtered result set spanning pages
- WHEN the page renders
- THEN the displayed count equals the paginator total, not an unfiltered total

#### Scenario: Equal timestamps remain stable
- GIVEN matching rows with equal timestamps across pages
- WHEN consecutive pages render
- THEN each row appears once in deterministic order

### Requirement: Responsive, safe audit presentation

The system MUST provide scan-friendly desktop records and stacked mobile records. It MUST show loading, empty, and fail-closed error states without partial rows or exception details.

#### Scenario: Records adapt to viewport
- GIVEN matching audit entries
- WHEN the surface renders on desktop and mobile viewports
- THEN desktop provides scannable rows and mobile provides stacked activity records

#### Scenario: Safe states are explicit
- GIVEN a loading request, no matching rows, or a query failure
- WHEN the surface renders
- THEN it shows the respective loading, empty, or safe error state; failure shows no rows or internals

### Requirement: Safe DTO-only projection and bounded scope

The surface MUST expose only the safe DTO fields defined by Sprint 6.2a and MUST NOT search or render payload JSON, `properties`, `attribute_changes`, secrets, or raw models. It MUST NOT add exports, charts, schema, capture-seam, backfill, navigation restructuring, or a generic table framework.

#### Scenario: Sensitive payload remains absent
- GIVEN a global row containing sensitive payload fields
- WHEN the surface is filtered and rendered
- THEN neither payload fields nor their values appear in the response or UI

#### Scenario: Excluded capabilities are unavailable
- GIVEN an authorized user views the audit surface
- WHEN the available controls and outputs are inspected
- THEN no payload search, export, chart, write control, or excluded structural feature is present
