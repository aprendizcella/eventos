# Commission Tracking Specification

## Purpose

Calculate and persist internal commission data for paid orders and refunds using exact billing values and organizer commission policy.

## MODIFIED Requirements

### Requirement: Commission Calculation

The system MUST calculate commission amounts from confirmed payments using exact, deterministic values.

#### Scenario: Paid order generates commission data

- GIVEN a confirmed payment for an order
- WHEN commission tracking runs
- THEN the system MUST calculate and store the commission amount

### Requirement: Commission Policy

The system MUST allow the commission policy to follow organizer settings first, then platform settings, then a hardcoded default.

#### Scenario: Organizer changes commission policy

- GIVEN an authorized organizer administrator
- WHEN the commission policy is updated
- THEN the system MUST persist the policy for later payout calculations

#### Scenario: Platform fallback applies when organizer is absent
- GIVEN no organizer commission setting exists
- WHEN commission is calculated
- THEN the platform setting MUST be used

### Requirement: Refund Adjustment

The system MUST adjust commission tracking when a refund is processed.

#### Scenario: Refund reverses commission impact

- GIVEN a payout linked to a paid order
- WHEN the order is refunded fully or partially
- THEN the commission totals MUST be adjusted or reversed accordingly

### Requirement: Exact Precision

The system MUST calculate commission totals without float drift for new tracking records.

#### Scenario: Commission amounts remain exact

- GIVEN a payment with fractional amounts
- WHEN the commission is calculated
- THEN the stored tracking values MUST remain exact and deterministic

### Requirement: Historical Immutability

The system MUST keep historical payouts immutable when platform settings change and MUST apply new values only to future payouts.

#### Scenario: Existing payout does not change
- GIVEN a historical payout already recorded
- WHEN platform commission settings are updated
- THEN the historical payout MUST remain unchanged
