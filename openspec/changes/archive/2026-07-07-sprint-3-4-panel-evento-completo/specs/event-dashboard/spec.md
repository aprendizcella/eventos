# Event Dashboard Specification

## Purpose

Provide organizers with a real-time operational dashboard for a single event.

## Requirements

### Requirement: KPI Summary

The dashboard MUST display net revenue, tickets sold, check-in rate, and waitlist request counts.

#### Scenario: Organizer opens dashboard

- GIVEN an authorized organizer user
- WHEN the user opens the event dashboard
- THEN the dashboard shows the KPI summary cards

### Requirement: Sales History

The dashboard MUST render a daily sales history chart.

#### Scenario: Sales chart is displayed

- GIVEN an event with sales history
- WHEN the dashboard is rendered
- THEN the chart displays the daily revenue series

### Requirement: Live Refresh

The dashboard MUST refresh automatically at a short interval.

#### Scenario: Dashboard auto-updates

- GIVEN the dashboard is open
- WHEN the refresh interval elapses
- THEN the metrics are refreshed without full navigation
