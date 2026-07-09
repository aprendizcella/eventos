# Tasks: Sprint 4.1 — Facturación

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 650-900 |
| 400-line budget risk | High |
| Chained PRs recommended | No |
| Suggested split | Sprint 4.1a → Sprint 4.1b → Sprint 4.1c |
| Delivery strategy | sequential-main-commits |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely Slice | Notes |
|------|------|-------------|-------|
| 1 | Exact billing foundation + invoice schema | Sprint 4.1a | Money precision, invoice model, core relations. |
| 2 | Automated invoice and credit-note flow | Sprint 4.1b | Payment/refund listeners, PDF generation, download. |
| 3 | Billing UX and reports | Sprint 4.1c | Event/org settings, report pages, CSV export. |

## Sprint 4.1a: Foundation (precision + invoice series)

- [x] 1.1 Add exact-precision billing foundation for invoice calculations and report totals; avoid extending `float` usage in the new flow.
- [x] 1.2 Create `invoice` persistence and `Invoice` model with relations to `Payment`, `Refund`, and `TicketOrder`, using organizer/year numbering.
- [x] 1.3 Add billing settings storage needed by event and organizer surfaces (invoice enablement, tax/fee metadata).
- [x] 1.4 Verify the foundation with model/request tests and a focused QA pass before moving on.

## Sprint 4.1b: Core Billing Flow

- [x] 2.1 Add `GenerateInvoiceAction` and listener(s) on payment completion so one paid order yields one invoice.
- [x] 2.2 Add `IssueCreditNoteAction` and listener(s) on refund completion so partial/full refunds create the right credit note.
- [x] 2.3 Add `InvoicePdfGenerator` and an invoice download surface from the panel.
- [x] 2.4 Add feature tests for invoice generation, PDF download, and credit-note creation.

## Sprint 4.1c: UX and Reports

- [ ] 3.1 Extend `resources/views/livewire/organizers/events/event-settings.blade.php` with the Hi.Events-style payment/facturation block.
- [ ] 3.2 Extend `resources/views/livewire/organizers/settings.blade.php` and organizer reports with taxes/platform fees settings and summaries.
- [ ] 3.3 Add report pages for income, taxes, and platform fees with filters and CSV export.
- [ ] 3.4 Add feature tests for billing settings persistence, authorization, and report rendering.

## Sprint 4.1 Final Verification

- [ ] 4.1 Run `composer qa` and fix regressions before implementation is declared complete.
