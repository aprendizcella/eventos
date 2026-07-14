# Public Catalog Specification

## Purpose

Provide a public event catalog that is tenant-aware, searchable through simple filters, and visible without authentication.

## Requirements

### Requirement: Public Catalog Route

The system MUST expose a public catalog route that is accessible without authentication on the root domain and on organizer domains.

#### Scenario: Guest opens public catalog
- GIVEN a guest user
- WHEN the user visits the public catalog route
- THEN the system MUST render the catalog page
- AND no authentication MUST be required

### Requirement: Root Domain Catalog Scope

The system MUST show all published public events from all organizers when the request host matches the application root domain.

#### Scenario: Root domain shows global catalog
- GIVEN a request to the root domain resolved from `config('app.url')`
- WHEN the catalog is rendered
- THEN events from all organizers MUST be eligible for display
- AND only published public events MUST appear

### Requirement: Organizer Domain Catalog Scope

The system MUST show only the current organizer's published public events when the request host resolves to an organizer domain.

#### Scenario: Organizer domain shows scoped catalog
- GIVEN a request to a organizer custom domain
- WHEN the catalog is rendered
- THEN only that organizer's published public events MUST appear

### Requirement: Event Visibility Filter

The system MUST exclude events that are not public or not published from the public catalog.

#### Scenario: Private event is hidden
- GIVEN an event with visibility `private`
- WHEN the public catalog is rendered
- THEN that event MUST NOT appear

#### Scenario: Unpublished event is hidden
- GIVEN an event with status other than `published`
- WHEN the public catalog is rendered
- THEN that event MUST NOT appear

### Requirement: Catalog Filters

The system MUST allow filtering the public catalog by category, city, and date, and the filters MUST work together with search.

#### Scenario: Filter by category
- GIVEN public events in multiple categories
- WHEN the category filter is applied
- THEN only events in the selected category MUST remain

#### Scenario: Filter by city
- GIVEN public events in multiple cities
- WHEN the city filter is applied
- THEN only events in the selected city MUST remain

#### Scenario: Filter by date
- GIVEN public events on different dates
- WHEN the date filter is applied
- THEN only events matching the selected date MUST remain

#### Scenario: Search and filters combine
- GIVEN a text query and active filters
- WHEN results are returned
- THEN the system MUST apply both search and filters together

### Requirement: Empty State

The system MUST show a friendly empty state when no events match the current filters.

#### Scenario: No results are found
- GIVEN filters or search return no events
- WHEN the catalog is rendered
- THEN the system MUST show an empty state message
- AND the page MUST keep the active search and filters visible

## Out of Scope

- Full-text search
- Featured events ranking
- SEO metadata
- Widget embedding
