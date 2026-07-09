# Tasks: Sprint 4.2 — Comisiones y Payouts

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 500-800 |
| 400-line budget risk | High |
| Chained PRs recommended | No |
| Suggested split | Sprint 4.2a -> Sprint 4.2b -> Sprint 4.2c |
| Delivery strategy | sequential-main-commits |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely Slice | Notes |
|------|------|-------------|-------|
| 1 | Commission foundation + payout model | Sprint 4.2a | Exact commission math, payout record schema, status enum. |
| 2 | Payout lifecycle + refund adjustments | Sprint 4.2b | Payment/refund listeners, state transitions, idempotency. |
| 3 | UX and reports | Sprint 4.2c | Settings simulation, payout reports, filters, CSV export. |

## Sprint 4.2a: Foundation (commission math + payout schema)

- [x] 1.1 Create `payout` persistence, model, factory and relations.
- [x] 1.2 Add `PayoutStatus` and the minimal internal lifecycle fields.
- [x] 1.3 Implement `CommissionCalculator` using exact billing values.
- [x] 1.4 Add unit tests for calculation and model defaults.

## Sprint 4.2b: Core Payout Flow

- [x] 2.1 Add `CreatePayoutAction` and wire it to payment confirmation.
- [x] 2.2 Add `AdjustPayoutAction` and wire it to refund processing.
- [x] 2.3 Ensure the flow is idempotent and can reverse/adjust payouts safely.
- [x] 2.4 Add feature tests for payout creation, refund adjustments and state transitions.

## Sprint 4.2c: UX and Reports

- [x] 3.1 Extend organizer settings with a commission policy/simulation section inspired by Hi.Events.
- [x] 3.2 Add payout report pages with filters, totals and CSV export.
- [x] 3.3 Add warning/help copy that clarifies the view is operational tracking, not external settlement.
- [x] 3.4 Add feature tests for settings rendering, authorization and report behavior.

## Sprint 4.2 Final Verification

- [x] 4.1 Run `composer qa` and fix regressions before implementation is declared complete.
