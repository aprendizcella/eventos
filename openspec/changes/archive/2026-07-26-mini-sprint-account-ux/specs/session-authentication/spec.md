# Session Authentication Specification

## Purpose

Provide explicit, opt-in remember-me behavior during web login so that long-lived sessions are only created when users explicitly choose to enable them.

## Requirements

### Requirement: Remember Me Checkbox

The login form MUST include a remember-me checkbox that defaults to unchecked and only sends the remember parameter when checked.

#### Scenario: Checkbox is present and unchecked by default

- GIVEN a user on the login page
- WHEN the page renders
- THEN a remember-me checkbox is displayed
- AND the checkbox is unchecked by default

#### Scenario: User logs in without checking remember me

- GIVEN a user on the login page
- WHEN the user enters valid credentials and submits without checking remember me
- THEN the system authenticates the user
- AND the remember parameter is NOT sent in the request
- AND the session uses the default (short-lived) duration

#### Scenario: User logs in with remember me checked

- GIVEN a user on the login page
- WHEN the user enters valid credentials and checks the remember-me checkbox
- AND the user submits the form
- THEN the system authenticates the user
- AND the remember parameter IS sent in the request
- AND the session uses the long-lived duration

#### Scenario: Checkbox state is independent per visit

- GIVEN a user who previously logged in with remember me checked
- WHEN the user returns to the login page
- THEN the remember-me checkbox is unchecked by default

#### Scenario: Login fails but checkbox state is preserved

- GIVEN a user on the login page with remember me checked
- WHEN the user submits invalid credentials
- THEN the system displays a validation error
- AND the remember-me checkbox state is preserved in the form

## Out of Scope

- Session duration configuration (handled by framework defaults)
- Remember-me token rotation or revocation
- Cross-device session management
