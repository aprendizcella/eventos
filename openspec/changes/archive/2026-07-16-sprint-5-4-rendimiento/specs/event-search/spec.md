# Event Search Specification

## Purpose

Reduce repeated database work in the public event catalog without changing search eligibility or fallback behavior.

## Requirements

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

### Requirement: Existing Search Contract

The system MUST preserve published/public eligibility, structured filters, tenant scoping, and the Eloquent `LIKE` fallback defined by the existing event-search specification.

## Testing Notes

Cache reuse is verified with a database query listener. Query-plan assertions are not included because the test database and container engine do not provide a stable production-equivalent optimizer plan.
