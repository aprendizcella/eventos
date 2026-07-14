# Event Embed Widget Specification

## Purpose

Provide an embeddable widget that lists an organizer's published events.

## Requirements

### Requirement: Embeddable Event List Widget

The system MUST provide a widget that can be embedded on external sites.

#### Scenario: Widget renders public event list
- GIVEN an organizer with published public events
- WHEN an external site loads the widget
- THEN the widget MUST show the event title, date, and canonical link for each listed event

#### Scenario: Widget respects limit
- GIVEN a configured event limit
- WHEN the widget loads
- THEN the widget MUST return no more than the configured number of events

### Requirement: Widget Scope

The system MUST list only the requesting organizer's published public events.

#### Scenario: Other organizers are excluded
- GIVEN events from multiple organizers
- WHEN the widget is requested for one organizer
- THEN only that organizer's published public events MUST appear

#### Scenario: Hidden events are excluded
- GIVEN private or unpublished events for the organizer
- WHEN the widget is rendered
- THEN those events MUST NOT appear

### Requirement: Widget Payload Contract

The system MUST return a consumable embed response for the widget contract.

#### Scenario: Embed script is available
- GIVEN a valid widget request
- WHEN the widget endpoint is requested
- THEN the system MUST return the embed payload required by the contract

#### Scenario: Invalid request is rejected
- GIVEN an invalid or missing organizer reference
- WHEN the widget is requested
- THEN the system MUST return a non-disclosing error response

## Out of Scope

- Checkout or ticketing actions
- Advanced filters
- Widget styling beyond scoped markup
