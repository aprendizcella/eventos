# Event Management Specification

## Purpose

CRUD interno de eventos del organizer con filtros, relaciones, sanitización de descripción y auditoría.

## Requirements

### Requirement: Event Model

The system MUST provide an `Event` model with fields: `title` (string), `slug` (unique), `description` (text, sanitized HTML), `starts_at` (datetime), `ends_at` (nullable datetime), `status` (enum, default `draft`), `visibility` (enum, default `private`), `organizer_id` (FK), `category_id` (nullable FK), `venue_id` (nullable FK). The table MUST include soft deletes and activity logging.

#### Scenario: Event is created with minimum fields
- GIVEN valid `title`, `organizer_id`
- WHEN the event is persisted
- THEN the record MUST exist with `status = draft` and `visibility = private`

#### Scenario: Event slug uniqueness
- GIVEN an event with slug "mi-evento"
- WHEN another event is created with the same slug
- THEN the system MUST reject the duplicate

### Requirement: Description Sanitization

The system MUST sanitize the `description` field to remove dangerous HTML (scripts, event handlers) while preserving safe formatting tags.

#### Scenario: Script tags are stripped
- GIVEN a description containing `<script>alert(1)</script>`
- WHEN the event is saved
- THEN the stored description MUST NOT contain `<script>`

#### Scenario: Safe tags are preserved
- GIVEN a description containing `<p>Hello <strong>world</strong></p>`
- WHEN the event is saved
- THEN the stored description MUST retain `<p>` and `<strong>` tags

### Requirement: Event CRUD Actions

The system MUST provide invocable actions for `CreateEvent`, `UpdateEvent` that receive a DTO and return the model.

#### Scenario: Create event via action
- GIVEN a valid `CreateEventDTO`
- WHEN `CreateEventAction` is invoked
- THEN a new `Event` MUST be returned with the DTO values

#### Scenario: Update event via action
- GIVEN an existing event and a valid `UpdateEventDTO`
- WHEN `UpdateEventAction` is invoked
- THEN the event MUST reflect the updated values

### Requirement: Event Listing with Filters

The system MUST provide an endpoint to list events for the authenticated organizer with optional filters: `status`, `category_id`, `search` (title).

#### Scenario: List all organizer events
- GIVEN an organizer with 5 events
- WHEN the list endpoint is called without filters
- THEN all 5 events MUST be returned

#### Scenario: Filter by status
- GIVEN events in `draft` and `published` status
- WHEN filter `status=published` is applied
- THEN only `published` events MUST be returned

#### Scenario: Filter by search term
- GIVEN events titled "Concierto Rock" and "Festival Jazz"
- WHEN filter `search=rock` is applied
- THEN only "Concierto Rock" MUST be returned

### Requirement: Event Form Request Validation

The system MUST validate event input via `FormRequest` classes that produce DTOs through `toDto()`.

#### Scenario: Valid request produces DTO
- GIVEN a request with valid fields
- WHEN `toDto()` is called
- THEN a populated DTO MUST be returned

#### Scenario: Invalid request is rejected
- GIVEN a request missing `title`
- WHEN validation runs
- THEN a 422 response MUST be returned
