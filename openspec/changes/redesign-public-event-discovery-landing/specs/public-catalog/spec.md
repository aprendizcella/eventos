# Delta for Public Catalog

## MODIFIED Requirements

### Requirement: Public Catalog Route

The system MUST expose a public, discovery-first catalog at `/` that is accessible without authentication on the root domain and on organizer domains. Public navigation MAY provide access to anchored Features content, but it MUST NOT move discovery to another route or change catalog scope, visibility, search, filters, URL state, or pagination.
(Previously: The public catalog route was required to be accessible without authentication on root and organizer domains.)

#### Scenario: Guest opens public catalog
- GIVEN a guest user
- WHEN the user visits the public catalog route
- THEN the system MUST render the catalog page
- AND no authentication MUST be required

#### Scenario: Visitor returns to discovery
- GIVEN a visitor is viewing public navigation or Features content
- WHEN the visitor selects Discover Events
- THEN the system MUST navigate to `/`
- AND the catalog MUST remain the first substantive home interaction

#### Scenario: Organizer domain preserves catalog scope
- GIVEN a request to an organizer domain
- WHEN the visitor accesses Features or returns to discovery
- THEN only the organizer's published public events MUST remain eligible for display
