# Admin Authorization Specification

## Purpose

Define global admin access rules with explicit Spatie team 0 context and no tenant leakage.

## Requirements

### Requirement: Global Admin Context

The system MUST resolve admin authorization in the global Spatie context with team ID `0` and MUST NOT inherit any ambient tenant context.

#### Scenario: Global admin request uses team 0
- GIVEN an authenticated admin request
- WHEN authorization is evaluated
- THEN the system MUST use team ID `0`
- AND tenant-scoped roles MUST NOT leak into the decision

#### Scenario: Tenant context is ignored for admin access
- GIVEN a user has tenant-scoped permissions in another organizer
- WHEN the user accesses an admin route
- THEN only global admin permissions MUST be considered

### Requirement: Admin Role Matrix

The system MUST distinguish `super_admin` from `platform_admin` as follows: `super_admin` MAY manage global roles and all platform data; `platform_admin` MUST be limited to organizer-scoped role management and platform operations that do not assign global roles.

#### Scenario: Super admin can grant global role
- GIVEN a user with `super_admin`
- WHEN the user assigns a global role
- THEN the action MUST be allowed

#### Scenario: Platform admin cannot grant global role
- GIVEN a user with `platform_admin`
- WHEN the user assigns a global role
- THEN the action MUST be denied (403)

### Requirement: Sanctum Admin API Authorization

The system MUST protect admin API endpoints with Sanctum authentication and MUST reject unauthenticated or non-admin requests.

#### Scenario: Authenticated admin can call API
- GIVEN a Sanctum-authenticated `super_admin`
- WHEN an admin API endpoint is requested
- THEN access MUST be allowed

#### Scenario: Unauthenticated request is rejected
- GIVEN no valid Sanctum authentication
- WHEN an admin API endpoint is requested
- THEN access MUST be denied (401)
