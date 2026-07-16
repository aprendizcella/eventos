# Event Search Specification

## Purpose

Provide full-text search for the public event catalog.

## Requirements

### Requirement: Searchable Event Index

The system MUST index only public events with status `published`. The searchable text MUST cover event title and description only. Structured attributes MAY be indexed for filtering, but MUST NOT become free-text search fields.

#### Scenario: Published public event is indexed
- GIVEN an event with status `published` and visibility `public`
- WHEN the index is refreshed
- THEN the event MUST be searchable by title and description text

#### Scenario: Non-eligible event is excluded
- GIVEN an event that is not `published` or not `public`
- WHEN the index is refreshed
- THEN the event MUST NOT be indexed

### Requirement: Index Synchronization

The system MUST update the search index asynchronously after the database commit using the existing queue system.

#### Scenario: Event becomes searchable after commit
- GIVEN a matching event is created or updated successfully
- WHEN the database transaction commits
- THEN the system MUST queue an index update

#### Scenario: Event leaves indexed states
- GIVEN an indexed event changes to a non-`published` status or non-`public` visibility
- WHEN the change is committed
- THEN the system MUST remove the event from the index

### Requirement: Search Fallback

The system MUST use Eloquent filtering with `LIKE` fallback when Meilisearch is unavailable and MUST log the failure.

#### Scenario: Search remains available during Meilisearch failure
- GIVEN Meilisearch is unavailable
- WHEN a user submits a text search
- THEN the system MUST still return matching events through the fallback path
- AND the failure MUST be logged

### Requirement: Searchable Filter Attributes

The system MUST include organizer scope, category, city, and start date as filterable indexed attributes without treating those values as free-text fields. The searchable attributes MUST remain title and description only.

#### Scenario: Search combines text and structured filters
- GIVEN an eligible event matching title text and category
- WHEN the text query and category filter are applied
- THEN the event MUST remain in the filtered results

#### Scenario: Tenant filter prevents cross-organizer results
- GIVEN eligible events belonging to different organizers
- WHEN a tenant-scoped catalog search is performed
- THEN only the current organizer's events MUST be returned

### Requirement: Cached Catalog Reads

The system MUST cache Eloquent catalog and fallback search results with Redis-compatible cache tags. Cache keys MUST include query, filters, page, and page size. Event, category, and venue mutations MUST invalidate the catalog cache.

#### Scenario: Repeated fallback search reuses cache
- GIVEN Meilisearch is unavailable
- WHEN the same fallback search is executed twice without a catalog mutation
- THEN the second execution MUST reuse the cached result
- AND it MUST not issue another database query for that result

#### Scenario: Catalog mutation invalidates cache
- GIVEN a cached catalog result exists
- WHEN an event, category, or venue is saved or deleted
- THEN the catalog cache MUST be flushed

## Testing Notes

The functional test suite uses Scout's `database` driver with `SCOUT_QUEUE=false` because the test multitenancy setup cannot assert Scout's tenant-aware queue jobs reliably. Lifecycle eligibility and removal behavior are therefore verified through the model contract and explicit state-transition tests; real Meilisearch integration remains covered separately when the service is available.

Cache reuse is verified with a database query listener. Query-plan assertions are not included because the test database and container engine do not provide a stable production-equivalent optimizer plan.
