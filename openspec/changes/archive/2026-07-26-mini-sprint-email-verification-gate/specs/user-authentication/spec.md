# Delta for User Authentication

## MODIFIED Requirements

### Requirement: Web Auth Flows

The system MUST support registration, login, logout, password reset request, password reset completion, and email verification. After registration, unverified users MUST be redirected to the verification notice page. Verified email MUST be required to access authenticated app areas (dashboard, organizers, account/profile, account/password).

(Previously: verified email MUST NOT block Sprint 1.1 access; registration granted immediate full access.)

#### Scenario: User registers and is redirected to verification

- GIVEN a guest submits valid registration data
- WHEN registration succeeds
- THEN the user MUST exist and be authenticated
- AND the user is redirected to the verification notice page

#### Scenario: Unverified user cannot access app routes

- GIVEN an authenticated user with unverified email
- WHEN the user accesses dashboard, organizers, profile, or password routes
- THEN the system redirects to the verification notice page

#### Scenario: Invalid credentials are rejected

- GIVEN a guest submits invalid login credentials
- WHEN login is attempted
- THEN authentication MUST fail with validation-safe feedback
- AND no session MUST be created

#### Scenario: Password reset completes

- GIVEN a valid reset token exists
- WHEN the user submits a valid new password
- THEN the password MUST change and the reset token MUST no longer be reusable

#### Scenario: Pest acceptance

- GIVEN the email verification gate is complete
- WHEN Pest runs
- THEN feature tests MUST cover registration redirect, unverified blocking, verified access, resend throttle, and logout availability
