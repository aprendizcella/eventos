# Health Monitoring Specification

## Purpose

Expose the health of the application's critical dependencies for deployment and uptime monitoring.

## Requirements

### Requirement: Critical Dependency Checks

The system MUST expose `/health` as a JSON endpoint with checks for MySQL, Redis, the configured cache store, and Meilisearch.

#### Scenario: Dependencies are healthy
- GIVEN all configured critical dependencies are available
- WHEN a client requests `/health`
- THEN the endpoint MUST return HTTP 200
- AND every check result MUST have status `ok`

#### Scenario: A critical dependency fails
- GIVEN any configured critical health check fails
- WHEN a client requests `/health`
- THEN the endpoint MUST return HTTP 503
- AND the response MUST identify the failed check

### Requirement: Fresh Results

The endpoint MUST preserve the package-supported ability to request fresh results with the `fresh` query parameter. The application MAY configure the endpoint to run fresh checks by default.

#### Scenario: Fresh health check is requested
- GIVEN a client requests `/health?fresh=1`
- WHEN the endpoint responds
- THEN the configured checks MUST be executed before the JSON result is returned

## Testing Notes

Tests use a deterministic failing check and the configured test dependencies. Real external service failure injection is not required because the package check contract is exercised directly.
