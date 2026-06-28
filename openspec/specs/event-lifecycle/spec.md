# Event Lifecycle Specification

## Purpose

Definir los estados, visibilidad y transiciones permitidas para el ciclo de vida de un evento.

## Requirements

### Requirement: Event Status Enum

The system MUST provide an `EventStatus` enum with cases: `draft`, `published`, `paused`, `cancelled`. The initial status on creation MUST be `draft`.

#### Scenario: New event starts as draft
- GIVEN a newly created event
- WHEN its status is read
- THEN it MUST be `draft`

### Requirement: Event Visibility Enum

The system MUST provide an `EventVisibility` enum with cases: `private`, `public`. The default visibility MUST be `private`.

#### Scenario: New event visibility default
- GIVEN a newly created event
- WHEN its visibility is read
- THEN it MUST be `private`

### Requirement: Status Transitions

The system MUST enforce valid status transitions via a `PublishEventAction`, `PauseEventAction`, and `CancelEventAction`. Allowed transitions:
- `draft` → `published`
- `published` → `paused`
- `published` → `cancelled`
- `paused` → `published`
- `paused` → `cancelled`

Any other transition MUST be rejected.

#### Scenario: Publish a draft event
- GIVEN an event in `draft` with all required fields populated
- WHEN `PublishEventAction` is invoked
- THEN the event status MUST become `published`

#### Scenario: Publish incomplete event is rejected
- GIVEN an event in `draft` missing `starts_at`
- WHEN `PublishEventAction` is invoked
- THEN the action MUST reject the transition

#### Scenario: Invalid transition is rejected
- GIVEN an event in `cancelled`
- WHEN `PublishEventAction` is invoked
- THEN the action MUST reject the transition

#### Scenario: Pause a published event
- GIVEN an event in `published`
- WHEN `PauseEventAction` is invoked
- THEN the event status MUST become `paused`

#### Scenario: Cancel a published event
- GIVEN an event in `published`
- WHEN `CancelEventAction` is invoked
- THEN the event status MUST become `cancelled`

### Requirement: Publish Validation

The system MUST validate that an event has the minimum required data before transitioning to `published`: `title`, `starts_at`, and a non-empty `description`.

#### Scenario: Publish with all required fields
- GIVEN an event with `title`, `starts_at`, and non-empty `description`
- WHEN publish is attempted
- THEN the transition MUST succeed

#### Scenario: Publish without starts_at
- GIVEN an event without `starts_at`
- WHEN publish is attempted
- THEN the transition MUST be rejected with a validation error

### Requirement: Activity Logging on Transitions

The system MUST log each status transition in the activity log with the previous and new status.

#### Scenario: Publish is logged
- GIVEN an event transitioning from `draft` to `published`
- WHEN the transition completes
- THEN an activity record MUST exist with event `published` and the status change
