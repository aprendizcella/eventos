# Audit Classification Boundary Specification

## Purpose

Define the fail-closed persisted classification boundary for the existing read-only global audit surface.

## Requirements

### Requirement: Canonical persisted global predicate

The global audit surface MUST render an activity row only when its persisted values satisfy `organizer_id IS NULL AND is_global = true`. Classification MUST be determined only from those persisted fields and MUST NOT be inferred from request tenant context, payload content, or a row label.

#### Scenario: Explicit global row is rendered

- GIVEN a super-admin and an activity row with `organizer_id IS NULL` and `is_global = true`
- WHEN the global audit page loads
- THEN the row is present in the rendered audit results

#### Scenario: Active tenant context does not alter the predicate

- GIVEN a super-admin request with an active tenant context and matching global activity rows
- WHEN the global audit page loads
- THEN matching rows are evaluated only by the canonical persisted predicate

### Requirement: Non-global classifications are excluded without reclassification

Tenant rows and unclassified rows MUST NOT render in the global audit surface. A tenant row is any row with a non-null `organizer_id`; an unclassified row has `organizer_id IS NULL` and `is_global = false`. Exclusion MUST NOT modify persisted classification, create replacement rows, or treat either excluded classification as global.

#### Scenario: Tenant and unclassified rows are absent

- GIVEN global, tenant, and unclassified activity rows in the same audit page range
- WHEN a super-admin loads the global audit page
- THEN only the explicit global row is rendered
- AND the tenant and unclassified rows are absent

#### Scenario: Historical unclassified data remains unchanged

- GIVEN a historical unclassified activity row
- WHEN the global audit page loads
- THEN the row is absent from the result
- AND its persisted `organizer_id` and `is_global` values remain unchanged

### Requirement: Protected safe read contract is unchanged

This classification correction MUST NOT broaden authorization or data exposure. Only authenticated users with the exact `super_admin` role MUST access the global audit surface, independent of tenant context. Rendered rows MUST retain the existing safe scalar projection and MUST NOT expose `properties`, `attribute_changes`, raw payloads, or secrets.

#### Scenario: Authorization remains exact

- GIVEN an authenticated user without the exact `super_admin` role
- WHEN the user requests the global audit page
- THEN access is denied and no audit data is returned

#### Scenario: Safe projection remains unchanged

- GIVEN an authorized global row containing sensitive payload fields
- WHEN the global audit page renders the row
- THEN the response exposes no sensitive payload field

## Out of Scope

- Schema, audit capture, historical-data backfill, reclassification, routes, policies, Volt UI, pagination, and exclusion-observability expansion.
