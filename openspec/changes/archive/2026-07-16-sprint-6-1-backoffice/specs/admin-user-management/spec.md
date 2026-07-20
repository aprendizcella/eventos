# Admin User Management Specification

## Purpose

Define backoffice user administration for listing, editing, suspending, restoring, and initiating password resets.

## Requirements

### Requirement: User Administration

The system MUST allow authorized admins to list, view, and edit users across organizers.

#### Scenario: Admin lists users
- GIVEN an authorized admin
- WHEN the user list is requested
- THEN matching users MUST be returned

#### Scenario: View user details
- GIVEN an existing user
- WHEN the admin opens the user detail view
- THEN the user profile MUST be shown

### Requirement: Suspension and Restore

The system MUST allow user suspension and restoration, while preserving audit fields and session/token behavior.

#### Scenario: Suspend user invalidates access
- GIVEN an active user
- WHEN the admin suspends the user
- THEN the user MUST be marked suspended
- AND existing sessions or API tokens MUST become unusable

#### Scenario: Restore user re-enables access
- GIVEN a suspended user
- WHEN the admin restores the user
- THEN the user MUST return to active status

### Requirement: Password Reset Link

The system MUST allow admins to trigger a password reset link without revealing the user password.

#### Scenario: Admin sends reset link
- GIVEN a target user account
- WHEN the admin requests a password reset link
- THEN the system MUST send the reset flow to the user

### Requirement: Final Super Admin Protection

The system MUST prevent suspension or deletion of the final active `super_admin`.

#### Scenario: Last super admin is protected
- GIVEN one active `super_admin` remains
- WHEN an admin attempts to suspend that account
- THEN the system MUST reject the action

### Requirement: GDPR Deletion Deferred

The system MUST defer GDPR deletion and anonymization from this capability.

#### Scenario: Deletion request is out of scope
- GIVEN an admin requests user deletion
- WHEN the request is evaluated
- THEN the system MUST not perform GDPR deletion here
