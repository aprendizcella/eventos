# Platform Settings Specification

## Purpose

Define singleton platform-wide settings for commissions and moderation defaults.

## Requirements

### Requirement: Singleton Settings Record

The system MUST store platform settings in a single logical record with JSON-based settings data.

#### Scenario: Settings record exists once
- GIVEN platform settings are requested
- WHEN the record is loaded
- THEN a single settings object MUST represent the platform state

### Requirement: Concurrency and Validation

The system MUST validate settings changes and prevent lost updates through versioning or locking.

#### Scenario: Invalid settings are rejected
- GIVEN malformed commission or settings data
- WHEN the admin saves settings
- THEN validation MUST fail

#### Scenario: Concurrent update is rejected
- GIVEN another admin has already updated the record
- WHEN a stale save is submitted
- THEN the system MUST reject the conflicting write

### Requirement: Commission Fallback Precedence

The system MUST apply commission values using this precedence: organizer setting, then platform setting, then hardcoded default. An explicit zero MUST override fallback values.

#### Scenario: Organizer setting wins
- GIVEN an organizer commission setting exists
- WHEN commission is calculated
- THEN the organizer value MUST be used

#### Scenario: Explicit zero is honored
- GIVEN a platform commission value of `0`
- WHEN fallback is resolved
- THEN `0` MUST be treated as an explicit override

### Requirement: Future Payouts Only

The system MUST apply setting changes only to future payouts and MUST keep historical payouts immutable.

#### Scenario: Historical payout remains unchanged
- GIVEN a payout already generated
- WHEN platform commission settings change
- THEN the historical payout MUST remain unchanged
