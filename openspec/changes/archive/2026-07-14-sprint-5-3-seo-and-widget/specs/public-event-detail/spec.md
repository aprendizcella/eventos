# Delta for Public Event Detail

## MODIFIED Requirements

### Requirement: Public Event Detail Route

The system MUST expose a public event detail route that is accessible without authentication and MUST resolve public events by canonical slug.
(Previously: public detail route existed without canonical slug resolution.)

#### Scenario: Guest opens canonical event detail
- GIVEN a guest user
- WHEN the user visits a valid public event slug route
- THEN the system MUST render the event detail page

#### Scenario: Legacy numeric route redirects
- GIVEN a public published event with a slug
- WHEN the user visits the legacy numeric route
- THEN the system MUST return a 301 redirect to the slug route

### Requirement: Detail Visibility Rules

The system MUST only show events that are public and published, and MUST NOT leak metadata for hidden events.
(Previously: public detail page only enforced visibility.)

#### Scenario: Public published event is visible
- GIVEN an event with visibility `public` and status `published`
- WHEN the public detail page is rendered
- THEN the event MUST be shown

#### Scenario: Non-public event is not visible
- GIVEN an event with visibility `private`
- WHEN the public detail page is requested
- THEN the system MUST return not found or an equivalent non-disclosing response

#### Scenario: Unpublished event is not visible
- GIVEN an event with status other than `published`
- WHEN the public detail page is requested
- THEN the system MUST return not found or an equivalent non-disclosing response

### Requirement: Checkout Entry Point

The system MUST provide a clear checkout entry point from the public event detail page.
(Previously: checkout CTA existed without slug-specific context.)

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

- Admin routes, forms, and behavior
- In-page checkout
- Dynamic OG images
