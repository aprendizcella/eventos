# Role-Based Access Specification

## Purpose

Define Sprint 1.1 role seed data and permission middleware readiness.

## Requirements

### Requirement: Initial Roles

The system MUST provide the roles `super_admin`, `platform_admin`, `organizer_admin`, `organizer_editor`, `organizer_viewer`, and `attendee` using Spatie Permission conventions without renaming vendor tables.

#### Scenario: Roles are seeded
- GIVEN the application seed process runs
- WHEN role storage is inspected
- THEN all six initial roles MUST exist exactly once

#### Scenario: Seeder is repeated
- GIVEN the initial roles already exist
- WHEN the seed process runs again
- THEN duplicate roles MUST NOT be created

### Requirement: Permission Middleware Readiness

The system MUST expose role/permission checks through Laravel 12 middleware configuration for future protected routes.

#### Scenario: Protected route denies unauthorized user
- GIVEN a route requires a role the authenticated user lacks
- WHEN the user requests that route
- THEN access MUST be denied

#### Scenario: Protected route permits authorized user
- GIVEN a route requires a role the authenticated user has
- WHEN the user requests that route
- THEN access MUST be allowed

### Requirement: Role QA Acceptance

Pest tests MUST prove role availability and representative allow/deny authorization behavior.

#### Scenario: QA covers roles
- GIVEN Sprint 1.1 role work is complete
- WHEN Pest runs
- THEN tests MUST verify seeded roles and at least one positive and one negative authorization path
