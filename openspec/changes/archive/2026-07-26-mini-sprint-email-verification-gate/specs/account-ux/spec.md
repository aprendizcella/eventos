# Delta for Account UX

## MODIFIED Requirements

### Requirement: Authenticated-Only Access

The profile and password change routes MUST be accessible only to authenticated users with a verified email. Unverified authenticated users MUST be redirected to the verification notice page. Logout MUST remain available to all authenticated users regardless of verification status.

(Previously: authenticated users could access profile and password pages regardless of verification status.)

#### Scenario: Guest cannot access profile page

- GIVEN a guest (unauthenticated) user
- WHEN the user attempts to access the profile route
- THEN the system redirects to the login page

#### Scenario: Guest cannot access password change page

- GIVEN a guest (unauthenticated) user
- WHEN the user attempts to access the password change route
- THEN the system redirects to the login page

#### Scenario: Unverified user cannot access profile

- GIVEN an authenticated user with unverified email
- WHEN the user attempts to access the profile route
- THEN the system redirects to the verification notice page

#### Scenario: Unverified user cannot access password page

- GIVEN an authenticated user with unverified email
- WHEN the user attempts to access the password change route
- THEN the system redirects to the verification notice page

#### Scenario: Unverified user can logout

- GIVEN an authenticated user with unverified email
- WHEN the user clicks logout
- THEN the system ends the session and redirects to login

#### Scenario: Verified user accesses profile and password pages

- GIVEN an authenticated user with verified email
- WHEN the user navigates to the profile or password change route
- THEN the system renders the requested page
