# Tasks: Sprint 5.1 - Public Catalog

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 500-700 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | size-exception |
| Chain strategy | not applicable |

## Phase 1: SDD Documentation

- [x] 1.1 Capture exploration context for the public catalog scope.
- [x] 1.2 Define the proposal, scope, risks, and rollback plan.
- [x] 1.3 Define the public catalog and public detail specs.
- [x] 1.4 Define the implementation design and route/data flow.

## Phase 2: Public Discovery Implementation

- [x] 2.1 Replace the root welcome page with the public catalog route.
- [x] 2.2 Create the public catalog component with root-domain and tenant-domain scoping.
- [x] 2.3 Implement filters for category, city, and date.
- [x] 2.4 Create the reusable public event card component.

## Phase 3: Public Event Detail

- [x] 3.1 Create the public event detail route and component.
- [x] 3.2 Render event metadata, organizer context, and calendar actions.
- [x] 3.3 Add a clear CTA to the existing checkout flow.

## Phase 4: QA & Verification

- [x] 4.1 Add feature tests for root-domain catalog scope.
- [x] 4.2 Add feature tests for tenant-domain catalog scope.
- [x] 4.3 Add feature tests for public detail and checkout entry.
- [x] 4.4 Run Pint, PHPStan, and Pest.

## Phase 5: Archive

- [ ] 5.1 Prepare the verify report.
- [ ] 5.2 Archive the change once verification passes.
