# queue-observability Specification

## Purpose

The system MUST provide a minimal operational entry point for administrators to observe and manage background job activity through Horizon, using Redis-backed queues in production-like environments. This capability focuses on queue visibility and priority separation only; it does not introduce product analytics or business dashboards.

## Requirements

### Requirement: Restricted Horizon access

The system MUST restrict Horizon access to users with the `super_admin` or `platform_admin` role.

#### Scenario: Authorized admin can access Horizon

- GIVEN a signed-in user with `super_admin` or `platform_admin`
- WHEN the user opens the Horizon entry point
- THEN access is granted
- AND the user can view queue operations

#### Scenario: Non-admin cannot access Horizon

- GIVEN a signed-in user without an allowed admin role
- WHEN the user opens the Horizon entry point
- THEN access is denied

### Requirement: Redis-backed operational queues

The system MUST use Redis as the operational queue backend outside the test environment.

#### Scenario: Production-like environments use Redis queues

- GIVEN the application is running in a non-test environment
- WHEN jobs are dispatched
- THEN they are queued through Redis-backed operational queues

#### Scenario: Testing remains deterministic

- GIVEN the application is running in the test environment
- WHEN jobs are dispatched during tests
- THEN queue execution remains suitable for deterministic testing

### Requirement: Priority-separated job queues

The system MUST separate operational jobs into named queues by priority.

#### Scenario: Ticket emails are high priority

- GIVEN a ticket-related email job is dispatched
- WHEN the job is queued
- THEN it MUST be placed in the `tickets` queue

#### Scenario: Bulk emails are medium priority

- GIVEN a bulk email job is dispatched
- WHEN the job is queued
- THEN it MUST be placed in the `emails` queue

#### Scenario: Unclassified jobs use the default queue

- GIVEN a job has no dedicated operational queue
- WHEN the job is dispatched
- THEN it MUST use the `default` queue

### Requirement: Minimal backoffice entry point

The system MUST expose a minimal backoffice entry point to Horizon for authorized administrators.

#### Scenario: Admin sidebar shows Horizon link

- GIVEN a signed-in authorized admin
- WHEN the backoffice navigation is rendered
- THEN a Horizon link is visible

#### Scenario: Non-admin users do not see the link

- GIVEN a signed-in user without an allowed admin role
- WHEN the backoffice navigation is rendered
- THEN the Horizon link is not visible
