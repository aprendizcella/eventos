# Public Event Detail Specification

## Purpose

Expose a public event detail page that can be opened without login and serves as the entry point to checkout.

## Requirements

### Requirement: Public Event Detail Route

The system MUST expose a public event detail route that is accessible without authentication.

#### Scenario: Guest opens event detail
- GIVEN a guest user
- WHEN the user visits a valid public event detail route
- THEN the system MUST render the event detail page

### Requirement: Detail Visibility Rules

The system MUST only show events that are public and published.

#### Scenario: Public published event is visible
- GIVEN an event with visibility `public` and status `published`
- WHEN the public detail page is rendered
- THEN the event MUST be shown

#### Scenario: Non-public event is not visible
- GIVEN an event with visibility `private`
- WHEN the public detail page is requested
- THEN the system MUST return not found or an equivalent non-disclosing response

### Requirement: Checkout Entry Point

The system MUST provide a clear checkout entry point from the public event detail page.

#### Scenario: User proceeds to checkout
- GIVEN a public event detail page
- WHEN the user clicks the purchase CTA
- THEN the system MUST navigate to the existing checkout flow for that event

### Requirement: Calendar Actions

The system SHOULD expose calendar actions from the public detail page when event dates are available.

#### Scenario: Event has a start date
- GIVEN a public event with a valid start date
- WHEN the detail page is rendered
- THEN the page SHOULD offer calendar export or add-to-calendar actions

## Out of Scope

- Ticket selection UI on this page
- In-page checkout
- SEO metadata and social cards
