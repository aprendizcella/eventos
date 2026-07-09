# Payout Tracking Specification

## Purpose

Provide internal payout records, state transitions, and read-only operational reporting for organizers and platform admins.

## Requirements

### Requirement: Payout Record Creation

The system MUST create a payout record for each processed commissionable payment.

#### Scenario: Payment creates payout record

- GIVEN a confirmed payment that is commissionable
- WHEN payout tracking runs
- THEN the system MUST create a payout record linked to the organizer and source payment

### Requirement: Payout Lifecycle

The system MUST track payout records through operational states.

#### Scenario: Admin processes a payout

- GIVEN a payout in a pending or ready state
- WHEN an authorized admin marks it processed
- THEN the system MUST update the payout state and timestamp

### Requirement: Refund Impact

The system MUST allow refunds to create payout adjustments or reversals.

#### Scenario: Refund updates payout totals

- GIVEN a payout already created for a paid order
- WHEN a refund is completed
- THEN the system MUST update the payout totals or create a reversal record

### Requirement: Payout Reports

The system MUST expose payout reports with filters, totals, and CSV export.

#### Scenario: Organizer opens payout report

- GIVEN an organizer with payout activity
- WHEN the payout report is opened
- THEN the system MUST show the filtered summary table and allow CSV export
