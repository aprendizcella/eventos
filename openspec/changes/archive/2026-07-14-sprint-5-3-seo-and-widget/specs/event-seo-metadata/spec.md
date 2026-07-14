# Event SEO Metadata Specification

## Purpose

Describe SEO metadata required on public event detail pages.

## Requirements

### Requirement: Public Event Metadata

The system MUST render SEO metadata for public event detail pages.

#### Scenario: Metadata is present for a public event
- GIVEN a public published event
- WHEN the detail page is rendered
- THEN the page MUST include a title and description
- AND the page MUST include canonical, Open Graph, and Twitter Card metadata

#### Scenario: Metadata follows the active event
- GIVEN a request for a specific public event
- WHEN the page is rendered
- THEN the metadata MUST describe that event

### Requirement: Non-Indexable Hidden Events

The system MUST NOT expose SEO metadata for events that are not public and published.

#### Scenario: Private event request does not leak metadata
- GIVEN an event with visibility `private`
- WHEN the detail page is requested
- THEN the system MUST return a non-disclosing response
- AND no event metadata MUST be exposed

#### Scenario: Unpublished event request does not leak metadata
- GIVEN an event with status other than `published`
- WHEN the detail page is requested
- THEN the system MUST return a non-disclosing response

## Out of Scope

- Dynamic OG images
- Structured data markup
- Admin page metadata
