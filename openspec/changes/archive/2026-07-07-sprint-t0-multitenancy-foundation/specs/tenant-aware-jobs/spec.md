# Tenant-Aware Jobs Specification

## Purpose

Ensure queued work executes with the correct organizer tenant context.

## Requirements

### Requirement: Queue Tenant Context

The system MUST preserve the current tenant when dispatching jobs from tenant-scoped requests.

#### Scenario: Job dispatched from organizer request

- GIVEN an organizer-scoped request
- WHEN a job is dispatched
- THEN the job MUST retain the organizer tenant context

### Requirement: Async Restoration

The system MUST restore the tenant context when queued work is executed.

#### Scenario: Worker processes queued job

- GIVEN a tenant-aware job in the queue
- WHEN the worker runs it
- THEN the correct organizer MUST be current during execution

### Requirement: No Cross-Tenant Leakage

The system MUST NOT allow async work to read or mutate another organizer's data.

#### Scenario: Wrong tenant context is absent

- GIVEN a queued listener or job
- WHEN it runs
- THEN it MUST only access the organizer it was dispatched under
