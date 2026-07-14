# Event Sitemap Specification

## Purpose

Expose an XML sitemap for public discovery of published events.

## Requirements

### Requirement: Public XML Sitemap

The system MUST expose an XML sitemap at `/sitemap.xml`.

#### Scenario: Guest requests sitemap
- GIVEN a guest user
- WHEN the user requests `/sitemap.xml`
- THEN the system MUST return XML
- AND the response MUST be publicly accessible

### Requirement: Sitemap Event Inclusion

The system MUST include only published public events in the sitemap.

#### Scenario: Published public events are listed
- GIVEN multiple events with mixed visibility and status
- WHEN the sitemap is generated
- THEN only public published events MUST be included
- AND each entry MUST reference the canonical slug URL

#### Scenario: Hidden events are excluded
- GIVEN an event that is private or unpublished
- WHEN the sitemap is generated
- THEN that event MUST NOT appear

### Requirement: Sitemap Stability

The system SHOULD return a consistent sitemap for the same set of eligible events.

#### Scenario: Empty eligible set
- GIVEN no public published events exist
- WHEN the sitemap is requested
- THEN the system MUST return a valid empty XML sitemap

## Out of Scope

- Image sitemap entries
- Host-specific sitemap partitioning
- Search engine pinging
