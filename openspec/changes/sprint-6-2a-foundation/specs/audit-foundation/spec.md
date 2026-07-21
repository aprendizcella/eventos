# Audit Foundation Specification

## Purpose

Establish fail-closed activity ownership for `Organizer` (`organizers.id`) in one shared database.

## Requirements

### Requirement: Activity ownership schema

The system MUST add nullable `organizer_id` referencing `organizers.id`, non-null `is_global` defaulting to `false`, query indexes, and a reversible migration. Foreign-key deletion MUST preserve rows; its exact action remains a design decision.

#### Scenario: States

- GIVEN the existing activity table
- WHEN the foundation migration runs
- THEN tenant rows store an organizer, global rows are explicit, and legacy rows remain null/non-global

#### Scenario: Invalid ownership

- GIVEN an activity row with an unknown organizer identifier
- WHEN it is inserted or updated
- THEN the database rejects it through the foreign key

### Requirement: Central capture classification

Every new activity MUST pass through one central seam. Tenant events MUST store the resolved organizer and non-global state; explicit system events MUST store global state and no organizer. Historical rows MUST NOT use current context.

#### Scenario: Tenant capture

- GIVEN organizer A is the resolved tenant
- WHEN a tenant event is logged
- THEN exactly one activity stores organizer A and `is_global = false`

#### Scenario: Global capture

- GIVEN global context and an explicitly global event
- WHEN the event is logged
- THEN it stores `is_global = true` and `organizer_id = null`

### Requirement: Fail-closed classification

Null organizer plus non-global MUST mean unclassifiable, not global. Authorized queries MUST exclude these rows; context MUST NOT reinterpret them.

#### Scenario: Ambiguous capture

- GIVEN no organizer is resolved and the event is not explicitly global
- WHEN capture is attempted
- THEN it is rejected or explicitly stored unclassified, and is never visible through any audit scope

### Requirement: Deterministic historical backfill

Backfill MUST assign ownership only when immutable evidence maps a subject to exactly one organizer. Other evidence MUST remain unclassified. It MUST support dry-run, bounded execution, idempotent reruns, and classified/skipped/conflict/failure reporting.

#### Scenario: Proven subject

- GIVEN a legacy subject maps to exactly one organizer
- WHEN backfill runs
- THEN its row receives that organizer and a rerun changes nothing

#### Scenario: Ambiguous subject

- GIVEN subject evidence maps to zero or multiple organizers
- WHEN backfill runs, including dry-run
- THEN the row is not assigned, the report identifies it, and no tenant can see it

### Requirement: Immutable historical ownership

Queries and policies MUST use stored classification, never current relationships. Later deletion, reassignment, or role changes MUST NOT move an activity or make it global.

#### Scenario: No reassignment

- GIVEN an activity classified to organizer A
- WHEN its subject or causer is deleted or later associated with organizer B
- THEN the activity remains classified to A

### Requirement: Tenant-admin isolation

Tenant administrators MUST see only rows whose stored organizer equals the current organizer. Global, unclassified, and other-organizer rows MUST be excluded independently of UI filters and routes.

#### Scenario: Isolation

- GIVEN activities for organizers A and B plus global and unclassified rows
- WHEN an A administrator queries audit records
- THEN only A rows are returned, regardless of current URL or supplied organizer filter

### Requirement: Global super-admin boundary

Only global-context `super_admin` (`team 0`) MAY inspect classified organizer and explicit global rows. Tenant-context users, including tenant-scoped super-admins, MUST remain isolated. Filters MAY narrow authority only.

#### Scenario: Global filtering

- GIVEN a global `super_admin` and records for A, B, global, and unclassified states
- WHEN the user queries all records or filters by A
- THEN all classified/global records, or only A records respectively, are returned; unclassified rows remain excluded

## Non-goals

UI is deferred to `sprint-6-2b-audit-ui`; GDPR and MFA/TOTP remain separate.

## Evidence and design questions

Evidence: Boost reports Laravel 12.62/MySQL; schema lacks organizer context; code resolves `Organizer` and uses Permission teams. Confirm: FK delete, indexes, ambiguity policy, and command boundaries.
