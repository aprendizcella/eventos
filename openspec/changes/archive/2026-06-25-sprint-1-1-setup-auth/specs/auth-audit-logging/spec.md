# Auth Audit Logging Specification

## Purpose

Define explicit audit visibility for authentication events not fully covered by automatic model logging.

## Requirements

### Requirement: Auth Event Audit Trail

The system MUST record auditable activity for register, login, logout, password reset request, and password reset completion using Activitylog-compatible records.

#### Scenario: Registration is logged
- GIVEN a guest registers successfully
- WHEN registration completes
- THEN an auth activity MUST record the event and affected user

#### Scenario: Login and logout are logged
- GIVEN an existing user authenticates and later logs out
- WHEN both actions complete
- THEN separate auth activities MUST record login and logout

#### Scenario: Password reset is logged
- GIVEN a user requests and completes password reset
- WHEN each step succeeds
- THEN auth activities MUST record request and completion without storing secrets or tokens

### Requirement: Audit Privacy and Failure Boundaries

Audit logging MUST NOT expose credentials, reset tokens, or sensitive request payloads; failed auth attempts MAY be logged only if privacy-safe.

#### Scenario: Sensitive data is excluded
- GIVEN an auth event includes password or token input
- WHEN activity context is stored
- THEN secrets MUST NOT appear in persisted audit data

#### Scenario: Audit failure does not reveal secrets
- GIVEN activity logging fails during an auth flow
- WHEN the user receives feedback
- THEN the response MUST NOT expose internal logging details or secrets

### Requirement: Audit QA Acceptance

Pest tests MUST verify required auth events produce activity records and that stored properties exclude sensitive data.

#### Scenario: QA covers audit trail
- GIVEN Sprint 1.1 audit work is complete
- WHEN Pest runs
- THEN tests MUST assert activity records for register, login, logout, and password reset flows
