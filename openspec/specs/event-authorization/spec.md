# Event Authorization Specification

## Purpose

Definir los permisos de lectura y escritura sobre eventos, venues y categorías según el rol del organizer y el acceso del global admin.

## Requirements

### Requirement: Role-Based Permissions

The system MUST enforce the following permissions per organizer role:

| Action | admin | editor | viewer |
|--------|-------|--------|--------|
| List events/venues | YES | YES | YES |
| Create event/venue | YES | YES | NO |
| Update event/venue | YES | YES | NO |
| Publish/pause/cancel event | YES | YES | NO |

#### Scenario: Admin can create event
- GIVEN an authenticated user with `organizer_admin` role
- WHEN a create event request is submitted
- THEN access MUST be allowed

#### Scenario: Editor can update event
- GIVEN an authenticated user with `organizer_editor` role
- WHEN an update event request is submitted for the organizer's event
- THEN access MUST be allowed

#### Scenario: Viewer cannot create event
- GIVEN an authenticated user with `organizer_viewer` role
- WHEN a create event request is submitted
- THEN access MUST be denied (403)

#### Scenario: Viewer can list events
- GIVEN an authenticated user with `organizer_viewer` role
- WHEN the list events endpoint is requested
- THEN access MUST be allowed

### Requirement: Organizer Isolation

The system MUST ensure that users can only access events and venues belonging to their own organizer. Cross-organizer access MUST be denied regardless of role.

#### Scenario: Admin of organizer A cannot access organizer B event
- GIVEN an `organizer_admin` of organizer A
- WHEN they request an event belonging to organizer B
- THEN access MUST be denied (403 or 404)

### Requirement: Global Admin Access

The system MUST allow users with `super_admin` or `platform_admin` role to read and manage any event or venue across all organizers.

#### Scenario: Super admin lists all events
- GIVEN a user with `super_admin` role
- WHEN the events list endpoint is requested
- THEN events from all organizers MUST be returned

#### Scenario: Super admin can publish any event
- GIVEN a user with `super_admin` role
- WHEN a publish action is invoked on any event
- THEN access MUST be allowed regardless of organizer ownership

### Requirement: Policy Enforcement

The system MUST use a Laravel Policy (`EventPolicy`, `VenuePolicy`) to authorize all write operations. The policy MUST be registered and applied via middleware or controller authorization.

#### Scenario: Unauthorized write is blocked
- GIVEN a viewer attempting to update an event
- WHEN the policy `update` method is evaluated
- THEN it MUST return `false`

#### Scenario: Authorized write is permitted
- GIVEN an editor attempting to update an event of their organizer
- WHEN the policy `update` method is evaluated
- THEN it MUST return `true`
