# Commission Tracking Specification

## Purpose

Calculate and persist internal commission data for paid orders and refunds using exact billing values and organizer commission policy.

## Requirements

### Requirement: Commission Calculation

The system MUST calculate commission amounts from confirmed payments using exact, deterministic values.

#### Scenario: Paid order generates commission data

- GIVEN a confirmed payment for an order
- WHEN commission tracking runs
- THEN the system MUST calculate and store the commission amount

### Requirement: Commission Policy

The system MUST allow the organizer billing settings to define how the commission is borne.

#### Scenario: Organizer changes commission policy

- GIVEN an authorized organizer administrator
- WHEN the commission policy is updated
- THEN the system MUST persist the policy for later payout calculations

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
