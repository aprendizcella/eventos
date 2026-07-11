# Organizer Advanced Reports Specification

## Purpose

Provide a read-only organizer report center with exact summaries for revenue, taxes, platform fees, payouts and event performance.

## Requirements

### Requirement: Organizer Report Hub

The system MUST present a report hub for organizers with report cards or sections that summarize the available datasets.

#### Scenario: Organizer opens the report hub

- GIVEN an authenticated organizer user
- WHEN the report hub is opened
- THEN the system MUST show the available organizer report sections

#### Scenario: Organizer sees default filters

- GIVEN the report hub is opened
- WHEN the filters render
- THEN the system MUST default to the last 90 days and all currencies

### Requirement: Filtered Organizer Summaries

The system MUST display organizer report summaries filtered by date range, event and currency when those filters are provided.

#### Scenario: Organizer filters a report

- GIVEN an organizer with multiple events and orders
- WHEN the organizer applies a date or event filter
- THEN the system MUST return only the matching organizer totals

### Requirement: Exact Operational Totals

The system MUST compute organizer report totals from the exact billing and payout data already stored in the application.

#### Scenario: Summary totals are calculated

- GIVEN paid orders, refunds and payouts for an organizer
- WHEN the summary is generated
- THEN the system MUST show exact totals without new float drift

### Requirement: Organizer CSV Export

The system MUST allow the organizer to export the filtered report data as CSV.

#### Scenario: Organizer exports a report

- GIVEN a filtered organizer report
- WHEN the organizer requests export
- THEN the system MUST return a CSV file containing the matching rows

### Requirement: Organizer Scope Isolation

The system MUST only expose data that belongs to the current organizer.

#### Scenario: Organizer cannot see another organizer data

- GIVEN report data from another organizer
- WHEN an organizer opens a report
- THEN the system MUST exclude the other organizer data from the response

### Requirement: Organizer Empty State

The system MUST show an empty state when no matching data exists for the selected filters.

#### Scenario: Organizer has no matching rows

- GIVEN a filtered organizer report with no matching rows
- WHEN the report is rendered
- THEN the system MUST show an empty state instead of a broken table
