# Design: Sprint 4.1 — Facturación

## Technical Approach

Usar listeners de pagos/reembolsos como disparadores, Actions para la lógica de facturación y ViewModels para resúmenes. La UI se integra en las superficies existentes: `event-settings` para billing operativo y `organizers/settings` + reports para impuestos/tarifas.
La arquitectura base sigue siendo **single DB + `organizer_id` como scope**; billing, invoices y reports deben respetar ese aislamiento sin introducir una separación física por tenant. El slice `4.1a` resuelve primero la precisión exacta, la serie por organizador/año y el almacenamiento mínimo de settings.

## Delivery Strategy

- Implementación secuencial en `main`.
- Cada mini-sprint termina en un commit verificable.
- El primer slice resuelve la precisión monetaria antes de exponer la factura.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Money precision | Introduce exact-precision billing values for new invoice/report flows | Keep `float` everywhere / migrate all monetary fields immediately | Reduce risk while keeping the sprint acotado; invoices and reports need exact values first. |
| Invoice numbering | Sequence invoices by `organizer_id` and calendar year | Global series / per-event series | Avoid collisions and keep auditability clear for each organizer. |
| Invoice trigger | Generate invoices from `PaymentCompleted` and credit notes from `RefundProcessed` | Controller-driven generation / cron reconciliation | Domain events keep the flow idempotent and decoupled from HTTP. |
| UX placement | Keep billing settings inside existing event/organizer settings and reports in organizer reports | New standalone billing module | Matches Hi.Events hierarchy and the current organizer/event navigation. |
| Settings boundary | Event settings own payment/facturation behavior; organizer settings own tax/fee metadata | Single global billing screen | Mirrors the Hi.Events split and keeps the UI aligned with current repo surfaces. |

## Data Flow

`PaymentCompleted` → `GenerateInvoiceAction` → `invoice` record → `InvoicePdfGenerator` → download UI

`RefundProcessed` → `IssueCreditNoteAction` → invoice adjustment / credit note record

`Organizer/Event settings` → saved billing settings → UI controls and billing behavior

`BillingReportsViewModel` → filtered income/tax/fee summaries → report table + CSV export

`Invoice series` → organizer_id + year → unique sequential invoice number

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/*invoice*.php` | Create | Invoice persistence and numbering. |
| `database/migrations/*billing*.php` | Modify/Create | Billing settings and tax/fee configuration. |
| `app/Models/Invoice.php` | Create | Invoice aggregate and relations. |
| `app/Actions/Payments/GenerateInvoiceAction.php` | Create | Create invoice from paid order. |
| `app/Actions/Payments/IssueCreditNoteAction.php` | Create | Create credit note from refund. |
| `app/Listeners/Payments/*Invoice*.php` | Create/Modify | Hook into payment/refund events. |
| `app/ViewModels/Organizers/BillingReportsViewModel.php` | Create | Income/tax/platform-fee summaries. |
| `resources/views/livewire/organizers/events/event-settings.blade.php` | Modify | Billing settings block aligned with Hi.Events. |
| `resources/views/livewire/organizers/settings.blade.php` | Modify | Organizer tax/fee settings. |
| `resources/views/organizers/reports/*.blade.php` | Create | Billing report screens and export actions. |
| `routes/web.php` | Modify | Organizer billing/report routes. |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Numbering, exact-precision helpers, invoice state transitions | Direct model/action tests. |
| Integration | PaymentCompleted/RefundProcessed → invoice/credit note flow | Feature tests with real DB and events. |
| E2E | Billing settings render and report UX | Livewire/Volt feature tests for forms, filters, and exports. |

## Migration / Rollout

Ship in one functional migration slice, but keep legacy payment/order tables readable. If invoice persistence needs backfill, run it after the schema lands and before enabling download/report links.

Rollout will be staged as `4.1a` foundation, `4.1b` invoice flow, and `4.1c` billing UX/reports, each with QA before the next commit.

## Open Questions

None — invoice numbering is organizer/year scoped.
