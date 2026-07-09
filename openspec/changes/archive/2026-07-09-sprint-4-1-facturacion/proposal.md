# Proposal: Sprint 4.1 — Facturación

## Intent

Cerrar el hueco de monetización con facturas automáticas, notas de crédito y superficies de billing que sigan la UX operativa de Hi.Events: configuración de pago/facturación, tarifas de plataforma y reportes de ingresos/impuestos. La primera entrega (`4.1a`) fija la base monetaria exacta y la numeración por organizador/año antes de automatizar el flujo.

## Scope

### In Scope
- Base monetaria exacta para los nuevos importes de facturación y reportes.
- Generación automática de factura al confirmar un pago, con PDF descargable y número secuencial.
- Notas de crédito al procesar reembolsos totales o parciales.
- Configuración de billing en evento y organizador, alineada con Hi.Events.
- Informes read-only de ingresos, impuestos y tarifas de plataforma con filtros y export CSV.

### Out of Scope
- Payouts, settlement y comisiones avanzadas de plataforma.
- Contabilidad completa, multi-moneda y automatización fiscal legal.
- Rediseño global del panel fuera del flujo de billing.
- Cambio del modelo de tenancy o separación física de datos por BBDD.

## Capabilities

### New Capabilities
- `invoice-management`: alta, generación, descarga y crédito de facturas.
- `billing-settings`: ajustes de pago, facturación, impuestos y tarifas.
- `billing-reports`: resúmenes de ingresos, impuestos y tarifas de plataforma.

### Modified Capabilities
- None.

## Approach

Conservar el patrón actual del repo: listeners de dominio para disparar el flujo, Actions para la lógica de facturación, ViewModels para informes y Volt/Blade para integrar la UI dentro de `event-settings` y `organizer settings/reports`. La primera iteración (`4.1a`) solo habilita la base exacta, la persistencia de `invoice` y el almacenamiento mínimo de billing settings.

## Delivery

- Trabajo directo en `main`.
- Un commit por mini-sprint para mantener el riesgo controlado.
- Orden propuesto: `4.1a` base monetaria y esquema de factura, `4.1b` facturación automática, `4.1c` UX/reportes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New/Modified | `invoice` y ajustes de billing/impuestos/tarifas; primera base monetaria exacta. |
| `app/Models/` | New/Modified | `Invoice` y relaciones con `Payment`, `Refund`, `TicketOrder`, `Event`, `Organizer`. |
| `app/Actions/Payments/` | New | Generación de factura, crédito y PDF. |
| `app/Listeners/Payments/` | New/Modified | Disparo post-pago y post-reembolso. |
| `app/ViewModels/Organizers/` | New | Resúmenes de ingresos, impuestos y fees. |
| `resources/views/livewire/organizers/` | Modified/New | Billing settings y reportes con UX tipo Hi.Events. |
| `routes/web.php` | Modified | Rutas de billing/reports bajo organizer/event. |
| `tests/Feature/` | New/Modified | Cobertura de factura, settings, reportes y permisos. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Precisión monetaria por `float` legado | High | Priorizar precisión exacta antes de facturar. |
| Duplicación de factura o crédito | Med | Idempotencia en listeners y acciones. |
| Sprint demasiado amplio | High | Entregar en slices y cerrar cada mini-sprint con QA antes de continuar. |

## Rollback Plan

Revertir migraciones nuevas, listeners/actions de billing, vistas y rutas añadidas. Los cambios de billing deben quedar aislados para poder desactivar el flujo sin romper pagos existentes.

## Dependencies

- Sprint 2.3 (Payments/Refunds) y Sprint 3.4 (event settings/dashboard) ya verificados.
- UX de Hi.Events para estructura visual de settings y reports.
- Arquitectura vigente: single DB + `organizer_id` como scope; no se introduce multitenancy físico en este sprint.

## Success Criteria

- [ ] Una orden pagada genera una factura única y descargable.
- [ ] Un reembolso genera la nota de crédito correcta.
- [ ] El billing se puede configurar desde evento y organizer.
- [ ] Los informes de ingresos/impuestos/tarifas son visibles y exportables.
- [ ] QA pasa limpio.
