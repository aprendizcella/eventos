# Venue Management Specification

## Purpose

Gestionar venues (lugares) reutilizables, propiedad de un organizer y aislados entre organizers.

## Requirements

### Requirement: Venue Model

The system MUST provide a `Venue` model belonging to an `Organizer` via `organizer_id`. Required fields: `name`, `address`. Optional: `city`, `capacity`, `description`. The table MUST include soft deletes.

#### Scenario: Venue is created with required fields
- GIVEN a valid organizer and venue data (`name`, `address`)
- WHEN the venue is persisted
- THEN the record MUST exist linked to the organizer

#### Scenario: Venue requires organizer
- GIVEN no organizer context
- WHEN venue creation is attempted
- THEN the system MUST reject the operation

### Requirement: Organizer Isolation

The system MUST ensure that a venue is only accessible by users belonging to its owning organizer. Cross-organizer access MUST be denied.

#### Scenario: Organizer accesses own venues
- GIVEN organizer A with a venue
- WHEN a user of organizer A lists venues
- THEN the venue MUST appear

#### Scenario: Cross-organizer access denied
- GIVEN organizer A with a venue
- WHEN a user of organizer B requests organizer A's venue
- THEN the system MUST return 403 or 404

### Requirement: Venue CRUD

The system MUST provide actions and endpoints to create, update, and list venues scoped to the authenticated organizer.

#### Scenario: Create venue
- GIVEN an authenticated organizer editor
- WHEN a valid create request is submitted
- THEN the venue MUST be created and linked to the organizer

#### Scenario: Update own venue
- GIVEN an existing venue owned by the organizer
- WHEN a valid update request is submitted
- THEN the venue fields MUST be updated

#### Scenario: List venues
- GIVEN an authenticated organizer user
- WHEN the list endpoint is requested
- THEN only venues belonging to the organizer MUST be returned

### Requirement: Venue Reuse Across Events

The system MUST allow a venue to be referenced by multiple events within the same organizer.

#### Scenario: Multiple events share a venue
- GIVEN a venue used by two events
- WHEN either event is queried
- THEN both MUST reference the same venue record
