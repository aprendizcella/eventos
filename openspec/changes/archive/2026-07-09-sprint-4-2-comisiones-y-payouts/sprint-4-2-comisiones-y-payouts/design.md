# Design: Sprint 4.2 — Comisiones y Payouts

## Technical Approach

Usar el flujo ya existente de pagos y facturación como base exacta y añadir una capa interna de comisiones/payouts encima. El objetivo no es mover dinero real, sino registrar qué se debe pagar, a quién, cuándo y con qué resultado operativo. La UI debe seguir el patrón observado en Hi.Events: settings por pestañas, tarjeta de estado, simulación de tarifa, banner de ayuda y tablas con filtros + export.

## Delivery Strategy

- Implementación secuencial en `main`.
- Cada mini-sprint termina en un commit verificable.
- El primer slice resuelve modelo y cálculo; el segundo, estados y ajustes; el tercero, UX y reportes.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Money source | Reuse exact billing amounts from Sprint 4.1 as the base for commission tracking | Recalculate from legacy order floats / wait for Connect | Avoid precision drift and keep the new logic deterministic. |
| Money movement | Record-only payouts, no external transfers | Full Stripe Connect / manual off-platform payouts only | The user requested internal tracking first; this reduces legal and operational risk. |
| Commission policy | Store the commission bearer explicitly so the UI can model buyer-paid vs organizer-paid fees | Implicit default only | Hi.Events exposes this concept directly; the app should not hide a business rule that changes payout math. |
| Payout lifecycle | Pending -> ready -> processed -> reversed/failed | Single boolean status | Operations need traceability and refund handling. |
| Reporting surface | Organizer reports with filters, totals, and CSV export | Standalone finance module | Matches the existing organizer navigation and keeps the feature discoverable. |

## Data Flow

`PaymentCompleted` -> `CommissionCalculator` -> `CreatePayoutAction` -> `payout` record -> reports UI

`RefundProcessed` -> `AdjustPayoutAction` -> payout reversal/adjustment record -> updated totals

`Organizer settings` -> commission policy + fee metadata -> simulation and calculation inputs

`PayoutReportsViewModel` -> filtered payout summaries -> table + CSV export

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/*payout*.php` | Create | Payout persistence and lifecycle metadata. |
| `app/Enums/PayoutStatus.php` | Create | Internal payout state machine. |
| `app/Models/Payout.php` | Create | Payout aggregate and relations. |
| `app/Services/Payments/CommissionCalculator.php` | Create | Encapsulate commission math. |
| `app/Actions/Payments/CreatePayoutAction.php` | Create | Persist payout after payment confirmation. |
| `app/Actions/Payments/AdjustPayoutAction.php` | Create | Reverse or adjust payout after refund. |
| `app/ViewModels/Organizers/PayoutReportsViewModel.php` | Create | Totals, filters and CSV rows for payout views. |
| `resources/views/livewire/organizers/settings.blade.php` | Modify | Commission policy/simulation section. |
| `resources/views/livewire/organizers/reports/*.blade.php` | Create | Payout report screen and export actions. |
| `routes/web.php` | Modify | Organizer payout/report routes. |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Commission calculation, payout state transitions | Direct service/action tests. |
| Integration | PaymentCompleted/RefundProcessed -> payout lifecycle | Feature tests with real DB and domain events. |
| E2E | Settings and report UX | Livewire/Volt tests for forms, filters and CSV export. |

## Migration / Rollout

Ship the payout model and calculator first, then wire the lifecycle, then expose the UI. Keep the billing/invoice flow independent so Sprint 4.2 can be rolled back without touching Sprint 4.1 artifacts.

## Open Questions

None at this stage: the sprint is intentionally limited to internal tracking and does not include actual Stripe transfers.
