# Billing Settings Specification

## Purpose

Expose billing-related settings at event and organizer level using the same operational UX pattern as Hi.Events.

## Requirements

### Requirement: Event Billing Settings

The system MUST allow authorized event managers to configure payment and facturation options.

#### Scenario: Event settings are saved

- GIVEN an authorized event manager
- WHEN billing settings are submitted
- THEN the settings MUST be validated and persisted

### Requirement: Organizer Tax and Fee Settings

The system MUST allow authorized organizer users to configure tax and platform-fee metadata.

#### Scenario: Organizer updates billing metadata

- GIVEN an authorized organizer administrator
- WHEN the organizer billing form is submitted
- THEN the tax/fee settings MUST be stored

### Requirement: Billing Help Copy

The system SHOULD present concise helper text and warning states for payment/facturation fields.

#### Scenario: Help text is visible

- GIVEN the billing settings screen is rendered
- WHEN the user views the form
- THEN helper text and warnings MUST be visible for operational fields
