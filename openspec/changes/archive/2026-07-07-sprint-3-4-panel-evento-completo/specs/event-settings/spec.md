# Event Settings Specification

## Purpose

Allow organizers to configure operational event settings and notification templates.

## Requirements

### Requirement: Settings Form

The system MUST allow authorized organizers to update event settings.

#### Scenario: Organizer saves settings

- GIVEN an authorized organizer user
- WHEN the user submits the settings form
- THEN the event settings are validated and saved

### Requirement: Notification Templates

The system MUST allow managing event notification templates.

#### Scenario: Template is available for event use

- GIVEN an event with notification templates
- WHEN the settings page is rendered
- THEN the template list is available to the organizer

### Requirement: Authorization

The settings page MUST be protected by organizer-level authorization.

#### Scenario: Unauthorized user is blocked

- GIVEN a user without settings permissions
- WHEN the user attempts to access the settings page
- THEN access is denied
