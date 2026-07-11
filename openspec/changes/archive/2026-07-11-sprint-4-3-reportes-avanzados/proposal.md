# Proposal: Sprint 4.3 - Reportes Avanzados

## Intent

Cerrar la capa de reporting con dos superficies de lectura: reportes del organizer y reportes globales de admin/plataforma. La referencia visual de HI.EVENTS se usa como pauta de jerarquia y densidad de informacion, no como copia literal. El objetivo es que Eventos permita consultar datos de ingresos, impuestos, tarifas, payouts y rendimiento con filtros claros y export CSV.

## Scope

### In Scope
- Reportes read-only para organizer con resumenes, tablas y filtros por fecha/evento/moneda.
- Reportes globales para admin/plataforma con filtros por organizer, fecha y moneda.
- Cabeceras de reporte con KPI cards, estados vacios y banners contextuales.
- Export CSV desde ambas superficies.
- Reutilizacion de los importes exactos y los datos ya cerrados en Sprint 4.1 y 4.2.

### Report Families
- Organizer: resumen de ingresos, resumen de impuestos, resumen de tarifas de plataforma, resumen de payouts y rendimiento de eventos.
- Platform: resumen global por organizer, comparativa de ingresos, impuestos y tarifas, y totales globales de payouts.

### Default Filters
- Fecha: ultimos 90 dias.
- Moneda: todas.
- Organizer: bloqueado en organizer reports; seleccionable en platform reports.
- Event: disponible solo en organizer reports donde aplique.

### Out of Scope
- Edicion de datos de negocio desde los reportes.
- Stripe Connect, onboarding/KYC o settlement real.
- Export PDF/XLSX en la primera iteracion.
- Nuevas reglas de cobro o logica de payouts.

## Capabilities

### New Capabilities
- `organizer-advanced-reports`: vista y export de reportes para organizers.
- `platform-advanced-reports`: vista y export de reportes globales para admin/plataforma.

### Modified Capabilities
- `billing-reports`: ampliar las lecturas existentes con vistas mas ricas y agregados por scope.
- `payout-tracking`: reutilizar los datos internos para las vistas de reporting.

## Approach

Conservar el patron actual del repo: capa de lectura en ViewModels y/o servicios de consulta, controllers finos, y Volt/Blade para la presentacion. La solucion debe compartir filtros y agregaciones entre organizer y platform, pero mantener surfaces separadas para no mezclar permisos ni carga cognitiva. Los patrones de HI.EVENTS se adaptan como estructura: overview, filtros, banner, tabla y export.

La primera vista de cada scope debe ser una landing de reportes con tarjetas de seccion, no una tabla directa. Los banners solo se usan para aclaraciones operativas o legales, nunca para vender el estado del sistema.

## Delivery

- Trabajo directo en `main`.
- Un commit por slice para mantener el riesgo controlado.
- Orden propuesto: `4.3a` base compartida de reportes, `4.3b` reportes de organizer, `4.3c` reportes de admin/plataforma.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/ViewModels/` | New | ViewModels de reportes por scope y filtros compartidos. |
| `app/Services/` | New | Consultas o agregadores de reporting reutilizables. |
| `app/Http/Controllers/` | Modified/New | Entrypoints de organizer y admin para reportes. |
| `resources/views/livewire/` | Modified/New | Pantallas de reportes con cards, tablas, banners y export. |
| `routes/web.php` | Modified | Rutas para organizer reports y platform reports. |
| `tests/Feature/` | New/Modified | Cobertura de permisos, filtros, export y scopes. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Scope creep entre billing, payouts y analytics | High | Mantener reportes en lectura y separar claramente lo que es detalle operativo de lo que es contabilidad. |
| Duplicacion de KPIs con dashboards existentes | Med | Diferenciar resumen operativo de reporting historico. |
| Export formats adicionales sin libreria aprobada | Med | Limitar 4.3 a CSV y dejar PDF/XLSX para una decision posterior. |

## Rollback Plan

Revertir vistas, servicios, viewmodels y rutas de reporting. Los cambios deben quedar aislados para no tocar facturacion, payouts ni checkout.

## Dependencies

- Sprint 4.1 archivado y verificado.
- Sprint 4.2 archivado y verificado.
- Datos exactos de billing/payouts ya disponibles.
- Referencia UX de HI.EVENTS analizada y adaptada.

## Success Criteria

- [ ] El organizer puede entrar al centro de reportes y ver las 5 familias de reporte.
- [ ] El admin/plataforma puede ver reportes globales por organizer y periodo.
- [ ] Los filtros por defecto quedan preseleccionados y visibles.
- [ ] Las tablas muestran datos agregados exactos sin depender de floats nuevos.
- [ ] Ambos scopes permiten export CSV.
- [ ] QA pasa limpio.
