# Email Verification Gate Specification

## Purpose

Enforce email verification as a gate before authenticated users can access app areas, while keeping verification, resend, and logout flows available.

## Requirements

### Requirement: Verification Notice Page

The system MUST display a verification notice page to authenticated unverified users. The notice page, resend route, callback route, and logout route MUST be accessible without email verification.

#### Scenario: Unverified user redirected to notice

- GIVEN an authenticated user whose email is not verified
- WHEN the user accesses a verified-only route
- THEN the system redirects to the verification notice page

#### Scenario: Notice page renders for unverified user

- GIVEN an authenticated unverified user
- WHEN the user visits the verification notice route
- THEN the system renders the notice page with resend option

### Requirement: Verification Callback

The system MUST provide a signed callback route that marks the user's email as verified upon valid access and redirects appropriately.

#### Scenario: Valid verification link

- GIVEN an unverified user with a valid signed verification URL
- WHEN the user visits the callback URL
- THEN the system marks the email as verified
- AND redirects to the dashboard

#### Scenario: Invalid or expired link

- GIVEN a user with an invalid or expired verification URL
- WHEN the user visits the callback URL
- THEN the system displays an error message

### Requirement: Resend Verification Email

The system MUST allow unverified users to request a new verification email. Resend MUST be throttled and MUST send a notification on success.

#### Scenario: Resend succeeds

- GIVEN an authenticated unverified user
- WHEN the user requests a verification email resend
- THEN the system sends a new verification email
- AND displays a success notification

#### Scenario: Resend is throttled

- GIVEN an authenticated unverified user who recently requested a resend
- WHEN the user requests another resend within the throttle window
- THEN the system rejects the request with a throttle message

### Requirement: Verified-Only Access Gate

The dashboard, organizers, account/profile, and account/password routes MUST require a verified email. Unverified authenticated users MUST be redirected to the verification notice.

#### Scenario: Unverified user blocked from dashboard

- GIVEN an authenticated user with unverified email
- WHEN the user accesses the dashboard
- THEN the system redirects to the verification notice page

#### Scenario: Verified user accesses dashboard

- GIVEN an authenticated user with verified email
- WHEN the user accesses the dashboard
- THEN the system renders the dashboard

### Requirement: Pre-verified Users

Seeded and admin-created users MAY be pre-verified with `email_verified_at` set at creation time.

#### Scenario: Seeded user is pre-verified

- GIVEN a user created via seeder with pre-verified flag
- WHEN the user logs in
- THEN the user can access all authenticated routes without verification redirect

## Out of Scope

- Custom email templates beyond minimal Laravel notification behavior
- Email change flow, MFA, admin invitation flow
