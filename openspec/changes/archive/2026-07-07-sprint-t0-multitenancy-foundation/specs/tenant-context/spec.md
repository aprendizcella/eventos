# Tenant Context Specification

## Purpose

Resolve the current organizer tenant consistently while keeping a single-database model.

## Requirements

### Requirement: Host-Based Tenant Resolution

The system MUST resolve the current tenant from the request host when a matching organizer domain exists.

#### Scenario: Custom domain resolves organizer

- GIVEN an organizer with a configured domain
- WHEN the request host matches that domain
- THEN that organizer MUST become the current tenant

### Requirement: Route Compatibility

The system MUST preserve current organizer-scoped routes during the transition.

#### Scenario: Organizer route resolves tenant

- GIVEN an authenticated request to `organizers/{organizer}` routes
- WHEN the route organizer is present
- THEN the same organizer MUST be available as current tenant

### Requirement: Scoped Data Access

The system MUST keep organizer data isolated through `organizer_id` scoping in the single database.

#### Scenario: Cross-organizer access is denied

- GIVEN two organizers
- WHEN a user from organizer A requests organizer B data
- THEN the request MUST be denied or return not found
