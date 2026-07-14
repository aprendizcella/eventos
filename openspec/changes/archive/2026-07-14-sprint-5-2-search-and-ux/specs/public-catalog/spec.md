# Delta for Public Catalog

## ADDED Requirements

### Requirement: Catalog Search Bar

The system MUST show search inside the catalog above the filters. The search input MUST automatically submit after about 400ms of inactivity.

#### Scenario: User types a search term
- GIVEN the public catalog is visible
- WHEN the user enters text in the catalog search bar
- THEN the system MUST update the results after a short debounce

### Requirement: Search and Filter URL Sync

The system MUST synchronize search, category, city, and date filters to URL query parameters.

#### Scenario: Filters are shareable
- GIVEN a user applies search and filters
- WHEN the URL is read or reloaded
- THEN the same search and filter state MUST be restored

### Requirement: Catalog Result Ordering

The system MUST order results by Meilisearch relevance when text search is present. Without text, the system MUST order upcoming events by start date ascending, with date as the tie-breaker.

#### Scenario: Text search ranks by relevance
- GIVEN a non-empty search query
- WHEN results are returned
- THEN the most relevant events MUST appear first

#### Scenario: No text search orders by date
- GIVEN no search query is present
- WHEN results are returned
- THEN upcoming events MUST be ordered by start date ascending

### Requirement: Classic Pagination

The system MUST paginate catalog results with 12 events per page and MUST NOT use infinite scroll.

#### Scenario: First page is limited
- GIVEN more than 12 matching events exist
- WHEN the catalog is rendered
- THEN the page MUST show only 12 events

### Requirement: Empty State Actions

The system MUST show a contextual empty state when search or filters return no results and MUST offer Clear search and Reset filters actions.

#### Scenario: No results after filtering
- GIVEN active search or filters return no events
- WHEN the catalog is rendered
- THEN the system MUST show an empty state tailored to the query
- AND the user MUST be able to clear search or reset filters

### Requirement: Catalog Loading Feedback

The system SHOULD show a loading skeleton for catalog results while a debounced search or filter request is in progress.

#### Scenario: Search request is pending
- GIVEN the user has entered a search term or changed a filter
- WHEN the catalog request is being processed
- THEN a loading state SHOULD be visible
- AND the current page MUST remain structurally stable

### Requirement: Public Detail Breadcrumb

The system MUST show a public breadcrumb on the event detail page with a link back to the catalog and the current event title.

#### Scenario: User views event detail
- GIVEN a guest views a public event detail page
- WHEN the page is rendered
- THEN the breadcrumb MUST show `Events` and the event title
- AND `Events` MUST link back to the public catalog

### Requirement: Catalog Event Card

The system MUST show the minimum available price on event cards and MUST show `Sold out` when no ticket availability remains. The card SHOULD keep an optional extension point for future images.

#### Scenario: Event has availability
- GIVEN an event with priced tickets
- WHEN the card is rendered
- THEN the minimum available price MUST be visible

#### Scenario: Event is sold out
- GIVEN an event without ticket availability
- WHEN the card is rendered
- THEN `Sold out` MUST be visible

## MODIFIED Requirements

### Requirement: Catalog Filters

The system MUST allow filtering the public catalog by category, city, and date, and the filters MUST work together with search.

(Previously: The catalog only supported category, city, and date filters.)

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
- THEN only events matching the selected date or range MUST remain

#### Scenario: Search and filters combine
- GIVEN a text query and active filters
- WHEN results are returned
- THEN the system MUST apply both search and filters together

### Requirement: Empty State

The system MUST show a friendly empty state when no events match the current filters.

(Previously: Empty state only covered filter-only no-result cases.)

#### Scenario: No results are found
- GIVEN filters or search return no events
- WHEN the catalog is rendered
- THEN the system MUST show an empty state message
- AND the page MUST keep the active search and filters visible
