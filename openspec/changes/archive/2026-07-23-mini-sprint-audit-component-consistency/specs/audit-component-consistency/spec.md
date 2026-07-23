# Audit Component Consistency Specification

## Purpose

Align the protected Global Audit Log with established report and event-detail presentation patterns without changing its verified audit behavior or security contract.

## Requirements

### Requirement: Shared audit filter controls

The audit filter form MUST render its existing log, event, from-date, and to-date controls with the established shared select and date controls. Its Apply and conditional Reset actions MUST use the shared button control and MUST retain a compact inline presentation. The controls MUST preserve their existing `wire:model` bindings, labels, allowlisted choices, placeholders, form submission, reset action, validation feedback, and stable identifiers.

#### Scenario: Shared controls render existing audit bindings
- GIVEN an authorized user opens the audit log
- WHEN the filter form renders
- THEN each existing draft filter binding and allowed choice is available through a shared control
- AND Apply and, when filters are active, Reset render as compact actions

#### Scenario: Applying and resetting filters preserves state behavior
- GIVEN the user has entered draft filter values or is viewing a later result page
- WHEN the user applies valid values or selects Reset
- THEN the existing apply or reset behavior and pagination reset are preserved
- AND no new filter meaning or query behavior is introduced

### Requirement: Report-aligned audit hierarchy

The audit surface MUST use the established report/event-detail hierarchy for page spacing, responsive header, immutable-status cue, filter card, and result cards. It MUST retain the existing audit copy, active-filter feedback, and identifiable desktop and mobile record containers.

#### Scenario: Audit presentation follows the established hierarchy
- GIVEN an authorized user opens the audit log
- WHEN the page renders
- THEN the header, status cue, filter card, and result area follow the established platform hierarchy
- AND the audit-specific labels and active-filter feedback remain available

#### Scenario: Responsive record presentation remains available
- GIVEN the audit log has matching records
- WHEN the page is rendered for desktop and mobile presentation
- THEN the existing desktop record view and mobile stacked records remain available
- AND their safe audit fields and stable render hooks remain intact

### Requirement: Existing safe states and boundaries are unchanged

The presentation refactor MUST preserve loading, empty, and generic error states without exposing partial rows or exception details. It MUST NOT change the exact `super_admin` route or component-update authorization, the safe DTO-only projection, query/filter semantics, routes, policies, schema, navigation, or branding.

#### Scenario: Safe states remain fail-closed
- GIVEN the audit surface is loading, has no matching records, or encounters a query failure
- WHEN the page renders
- THEN the corresponding existing safe state is shown
- AND a failure exposes neither rows nor internal error details

#### Scenario: Security and data boundaries remain enforced
- GIVEN a non-super-admin user or an audit row with sensitive payload data
- WHEN the route or component is accessed and rendered
- THEN unauthorized access is denied and sensitive payload data is absent
- AND the pre-existing audit query and filter outcomes are unchanged

### Requirement: Strict-types-compatible Volt presentation

The audit Volt PHP section MUST use strict types and MUST remain compatible with the existing component lifecycle, validation, rendering, and filter interactions.

#### Scenario: Strict-types audit component renders and interacts
- GIVEN the strict-types audit component is mounted by an authorized user
- WHEN it renders, applies valid draft values, and resets active filters
- THEN the interactions complete without a type error
- AND the existing rendered audit behavior remains available
