# Proposal: Sprint 4.2 — Comisiones y Payouts

## Intent

Cerrar la capa interna de monetización con tracking de comisiones y payouts sin mover dinero real todavía. El sprint debe adaptar ideas de UX de Hi.Events - especialmente tarifas de plataforma, resumenes y tablas filtradas - para que Eventos permita calcular comisiones, registrar payouts y consultar su estado desde el panel, dejando Stripe Connect para una fase posterior.

## Scope

### In Scope
- Cálculo interno de comisiones a partir de pagos confirmados y reembolsos.
- Registro de payouts por organizer con estados operativos y trazabilidad.
- Ajuste de la UI de settings para exponer la política de comisión y su simulación.
- Vista de reportes de payouts/comisiones con filtros, totales y export CSV.
- Reversión o ajuste de payouts cuando se procese un refund.

### Out of Scope
- Transferencias reales de dinero con Stripe Connect.
- Onboarding/KYC de cuentas conectadas.
- Conciliación bancaria o contabilidad legal completa.
- Separación física de datos por base de datos.

## Capabilities

### New Capabilities
- `commission-tracking`: cálculo, persistencia y ajuste de comisiones internas.
- `payout-management`: creación, listado, filtrado y cierre operativo de payouts.

### Modified Capabilities
- `billing-settings`: añadir o ajustar la política de comisión y su simulación.
- `billing-reports`: extender los reportes con información de payouts y comisiones.

## Approach

Conservar el patrón actual del repo: Actions para el cálculo y la creación de payouts, ViewModels para la lectura, y Volt/Blade para las superficies de configuración y reportes. El flujo debe apoyarse en los importes exactos ya introducidos en Sprint 4.1 y evitar depender de `float` para nuevas cuentas internas. Stripe Connect queda explicitamente diferido.

## Delivery

- Trabajo directo en `main`.
- Un commit por mini-sprint para mantener el riesgo controlado.
- Orden propuesto: `4.2a` base de comisiones y payout records, `4.2b` flujo operativo y ajustes por refund, `4.2c` UX/reportes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | Tabla `payout` y, si hace falta, campos auxiliares para política de comisión. |
| `app/Models/` | New/Modified | `Payout` y relaciones con `Invoice`, `Payment`, `Refund`, `Organizer`. |
| `app/Actions/Payments/` | New | Cálculo de comisión, creación de payout, ajuste por refund. |
| `app/Services/` | New | `CommissionCalculator` para encapsular la fórmula. |
| `app/ViewModels/Organizers/` | New | Resúmenes de payouts/comisiones para reportes. |
| `resources/views/livewire/organizers/` | Modified/New | UI de settings y reports con patrones tipo Hi.Events. |
| `routes/web.php` | Modified | Rutas de payouts/reportes bajo organizer. |
| `tests/Feature/` | New/Modified | Cobertura de cálculo, payout lifecycle y report rendering. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Legacy `float` en pagos/órdenes arrastra errores de precisión | High | Calcular el tracking interno desde los importes exactos de facturación. |
| Refunds dejan payouts inconsistentes | Med | Crear ajustes/reversiones explícitas y cubrirlos con tests. |
| Scope creep hacia Stripe Connect | High | Mantener el sprint en tracking interno y diferir transferencias reales. |

## Rollback Plan

Revertir migraciones nuevas, actions, servicios, vistas y rutas de payouts. El flujo de facturación existente debe seguir intacto aunque se desactive el tracking interno.

## Dependencies

- Sprint 4.1 ya archivado y verificado.
- Sprint 2.3 como base de payments/refunds.
- UX de Hi.Events para la composición visual de settings y reports.

## Success Criteria

- [ ] Un pago confirmado genera un cálculo interno de comisión y un payout asociado.
- [ ] Un refund actualiza o revierte el payout afectado.
- [ ] El organizer puede ver la política de comisión y una simulación clara.
- [ ] Los reportes de payouts/comisiones son filtrables y exportables.
- [ ] QA pasa limpio.
