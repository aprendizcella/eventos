# Tasks: Sprint 4.3 - Reportes Avanzados

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 400-700 |
| 400-line budget risk | High |
| Chained PRs recommended | No |
| Suggested split | Sprint 4.3a -> Sprint 4.3b -> Sprint 4.3c |
| Delivery strategy | sequential-main-commits |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely Slice | Notes |
|------|------|-------------|-------|
| 1 | Shared reporting foundation | Sprint 4.3a | Common filters, default periods and query helpers. |
| 2 | Organizer report center | Sprint 4.3b | Revenue, taxes, fees, payouts and event performance. |
| 3 | Platform report center | Sprint 4.3c | Global metrics, organizer filter and cross-scope summaries. |

## Sprint 4.3a: Foundation (shared report layer)

- [x] 1.1 Define the shared filter shape for date, currency, organizer and event scopes, including the 90-day default.
- [x] 1.2 Implement shared report queries or services for aggregate totals and empty states.
- [x] 1.3 Add unit tests for aggregation correctness, default filters and scope enforcement.

## Sprint 4.3b: Organizer Reports

- [x] 2.1 Add the organizer reporting entrypoint and navigation.
- [x] 2.2 Build the organizer landing hub with 5 report families and section cards.
- [x] 2.3 Build summary tables, contextual banners and event drilldown where applicable.
- [x] 2.4 Add CSV export for organizer report datasets.
- [x] 2.5 Add feature tests for authorization, filters, empty states and export.

## Sprint 4.3c: Platform Reports

- [x] 3.1 Add the admin/platform reporting entrypoint and navigation.
- [x] 3.2 Build the platform landing hub with cross-organizer summary cards.
- [x] 3.3 Build global summaries with organizer filters and cross-organizer totals.
- [x] 3.4 Add CSV export for platform datasets.
- [x] 3.5 Add feature tests for admin scope, filters, empty states and export.

## Sprint 4.3 Final Verification

- [x] 4.1 Run QA and keep the change read-only.
