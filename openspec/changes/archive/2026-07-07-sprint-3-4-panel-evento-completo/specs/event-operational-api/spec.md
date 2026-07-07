# Event Operational API Specification

## Purpose

Expose operational event endpoints for attendees, check-in, and bulk messaging.

## Requirements

### Requirement: Attendee Listing

The API MUST expose the event attendees list for authorized organizer users.

#### Scenario: Authorized organizer lists attendees

- GIVEN an authenticated organizer user
- WHEN the user requests the attendees endpoint
- THEN the API returns the paginated attendee payload

### Requirement: Check-in

The API MUST allow check-in requests and return domain errors as client errors.

#### Scenario: Invalid check-in is rejected

- GIVEN an invalid or duplicate check-in attempt
- WHEN the user calls the check-in endpoint
- THEN the API returns a 4xx response with an error message

### Requirement: Bulk Messages

The API MUST allow sending bulk messages to event attendees.

#### Scenario: Authorized organizer sends messages

- GIVEN an authorized organizer user
- WHEN the user calls the bulk message endpoint
- THEN the API accepts the request and dispatches the message flow
