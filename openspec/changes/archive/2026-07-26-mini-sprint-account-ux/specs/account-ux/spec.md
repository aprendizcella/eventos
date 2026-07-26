# Account UX Specification

## Purpose

Provide authenticated users with visible, safe account self-service: a topbar dropdown for account context and logout, a profile page for name updates with read-only email, and a password change form with proper validation.

## Requirements

### Requirement: Topbar Account Dropdown

The topbar MUST display an account dropdown for authenticated users showing the current user's name, current role, current organizer context, a Profile link, and a Sign out action.

#### Scenario: Authenticated user opens dropdown

- GIVEN an authenticated user is logged in
- WHEN the user clicks the account dropdown trigger
- THEN the dropdown displays the user's name, role, and organizer context
- AND the dropdown contains a Profile link
- AND the dropdown contains a Sign out action

#### Scenario: Dropdown shows fallback when role or organizer is absent

- GIVEN an authenticated user has no resolved role or organizer context
- WHEN the user opens the dropdown
- THEN the dropdown displays fallback labels for missing role or organizer

#### Scenario: Guest user does not see dropdown

- GIVEN a guest (unauthenticated) user
- WHEN the user views the topbar
- THEN no account dropdown is rendered

### Requirement: Profile Page Name Edit

The profile page MUST allow authenticated users to edit their name and submit the update.

#### Scenario: User updates name successfully

- GIVEN an authenticated user on the profile page
- WHEN the user changes the name field and submits the form
- THEN the system validates the input
- AND the user's name is updated
- AND the system displays a success message

#### Scenario: Name validation fails

- GIVEN an authenticated user on the profile page
- WHEN the user submits an empty or invalid name
- THEN the system displays validation errors
- AND the name is not updated

### Requirement: Profile Page Email Read-Only

The profile page MUST display the user's email as read-only and MUST NOT submit or modify the email field.

#### Scenario: Email is displayed but not editable

- GIVEN an authenticated user on the profile page
- WHEN the user views the email field
- THEN the email is displayed as read-only text or a disabled input
- AND the email field is not included in form submission

#### Scenario: Email cannot be modified via form

- GIVEN an authenticated user attempts to submit the profile form
- WHEN the form is submitted
- THEN only the name field is processed
- AND the email remains unchanged in the database

### Requirement: Password Change

The system MUST allow authenticated users to change their password after validating the current password and confirming the new password.

#### Scenario: User changes password successfully

- GIVEN an authenticated user on the password change page
- WHEN the user provides the correct current password, a valid new password, and matching confirmation
- THEN the system validates all inputs
- AND the password is updated
- AND the system displays a success message

#### Scenario: Current password is incorrect

- GIVEN an authenticated user on the password change page
- WHEN the user provides an incorrect current password
- THEN the system displays a validation error
- AND the password is not changed

#### Scenario: New password confirmation does not match

- GIVEN an authenticated user on the password change page
- WHEN the new password and confirmation do not match
- THEN the system displays a validation error
- AND the password is not changed

#### Scenario: New password fails validation rules

- GIVEN an authenticated user on the password change page
- WHEN the new password does not meet minimum requirements (e.g., too short)
- THEN the system displays validation errors
- AND the password is not changed

### Requirement: Authenticated-Only Access

The profile and password change routes MUST be accessible only to authenticated users.

#### Scenario: Guest cannot access profile page

- GIVEN a guest (unauthenticated) user
- WHEN the user attempts to access the profile route
- THEN the system redirects to the login page

#### Scenario: Guest cannot access password change page

- GIVEN a guest (unauthenticated) user
- WHEN the user attempts to access the password change route
- THEN the system redirects to the login page

#### Scenario: Authenticated user can access profile and password pages

- GIVEN an authenticated user
- WHEN the user navigates to the profile or password change route
- THEN the system renders the requested page

## Out of Scope

- Email change flow (future: must trigger re-verification)
- Avatar upload (future-ready UI extension point only)
- Multi-factor authentication
- Account deletion
- Notification preferences
