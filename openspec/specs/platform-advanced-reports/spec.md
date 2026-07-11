# Platform Advanced Reports Specification

## Purpose

Provide a read-only admin/platform reporting surface with cross-organizer metrics, filters and CSV export.

## Requirements

### Requirement: Platform Report Hub

The system MUST present a platform report hub for admin users with aggregate report sections.

#### Scenario: Admin opens the platform report hub

- GIVEN an authenticated platform admin
- WHEN the report hub is opened
- THEN the system MUST show the global reporting sections

#### Scenario: Admin sees default filters

- GIVEN the report hub is opened
- WHEN the filters render
- THEN the system MUST default to the last 90 days and all currencies

### Requirement: Cross-Organizer Aggregation

The system MUST aggregate report totals across organizers and allow filtering by organizer when needed.

#### Scenario: Admin filters by organizer

- GIVEN multiple organizers with report activity
- WHEN the admin selects one organizer filter
- THEN the system MUST show only the totals for that organizer

### Requirement: Platform Operational Metrics

The system MUST display platform-level summary metrics that help review global revenue, taxes, fees and payout activity.

#### Scenario: Admin reviews global metrics

- GIVEN a reporting period with activity across organizers
- WHEN the admin opens the report hub
- THEN the system MUST show platform summary metrics for that period

### Requirement: Platform CSV Export

The system MUST allow the platform admin to export the filtered global report data as CSV.

#### Scenario: Admin exports a platform report

- GIVEN a filtered platform report
- WHEN the admin requests export
- THEN the system MUST return a CSV file containing the matching rows

### Requirement: Platform Access Control

The system MUST restrict platform reports to users with admin or superadmin access.

#### Scenario: Non-admin user is denied

- GIVEN a non-admin authenticated user
- WHEN the platform report hub is requested
- THEN the system MUST deny access

### Requirement: Platform Empty State

The system MUST show an empty state when no matching data exists for the selected filters.

#### Scenario: Admin has no matching rows

- GIVEN a filtered platform report with no matching rows
- WHEN the report is rendered
- THEN the system MUST show an empty state instead of a broken table
