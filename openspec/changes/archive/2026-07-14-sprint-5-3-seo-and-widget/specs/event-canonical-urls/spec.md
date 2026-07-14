# Event Canonical URLs Specification

## Purpose

Define slug-based public event URLs and preserve legacy numeric URLs with redirects.

## Requirements

### Requirement: Canonical Public Event URL

The system MUST expose each public event on a canonical slug-based URL.

#### Scenario: Guest opens canonical event URL
- GIVEN a public published event with a slug
- WHEN the guest visits the slug URL
- THEN the system MUST render the public event detail page
- AND the URL MUST remain the slug URL

#### Scenario: Missing slug is not canonical
- GIVEN a public published event without a resolvable slug
- WHEN the user requests the public detail page
- THEN the system MUST return a non-disclosing response

### Requirement: Legacy Numeric Redirect

The system MUST redirect legacy numeric public event URLs to the canonical slug URL using HTTP 301.

#### Scenario: Numeric URL redirects to slug
- GIVEN a public published event with an existing slug
- WHEN the user visits `/events/{id}`
- THEN the system MUST redirect permanently to `/events/{slug}`

#### Scenario: Unknown numeric event is not redirected
- GIVEN no public event matches the numeric identifier
- WHEN the user visits `/events/{id}`
- THEN the system MUST return not found or an equivalent non-disclosing response

## Out of Scope

- Admin event routes
- Slug generation rules
- SEO metadata rendering
