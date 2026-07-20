# Admin Event Moderation Specification

## Purpose

Define reversible platform moderation for events across organizers.

## Requirements

### Requirement: Reversible Suspension

The system MUST allow an admin to suspend an event and restore it to its previous status, where the previous status MUST be one of `draft`, `published`, or `paused`.

#### Scenario: Suspend published event
- GIVEN a `published` event
- WHEN the admin suspends it with a reason
- THEN the event MUST become suspended
- AND the previous status MUST be saved

#### Scenario: Restore suspended event
- GIVEN a suspended event with a saved previous status
- WHEN the admin restores it
- THEN the event MUST return to that previous status

### Requirement: Suspension Audit

The system MUST record the suspending actor and a mandatory reason for every suspension.

#### Scenario: Suspension requires reason
- GIVEN an admin suspends an event
- WHEN no reason is provided
- THEN the system MUST reject the action

#### Scenario: Suspension records actor
- GIVEN a valid suspension request
- WHEN the action succeeds
- THEN the actor MUST be stored in the audit record

### Requirement: Catalog and Search Exclusion

The system MUST exclude suspended events from public catalog and search results.

#### Scenario: Suspended event is hidden
- GIVEN a suspended event
- WHEN the catalog or search index is queried
- THEN the event MUST NOT appear

### Requirement: No Financial Side Effects

The system MUST NOT trigger automatic refunds or payout changes when an event is suspended or restored.

#### Scenario: Suspension does not refund
- GIVEN an event with paid orders
- WHEN the admin suspends the event
- THEN no automatic refund MUST be created
- AND no payout MUST be altered automatically
