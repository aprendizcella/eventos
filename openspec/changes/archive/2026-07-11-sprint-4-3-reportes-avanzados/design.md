# Design: Sprint 4.3 - Reportes Avanzados

## Technical Approach

Construir una capa de lectura compartida para reportes y exponer dos surfaces distintas: organizer y admin/plataforma. La UI debe seguir los patrones observados en HI.EVENTS: overview con tarjetas, filtros arriba, tablas densas, banners contextuales y export visible. No se copia el layout; se adapta la jerarquia de informacion al sistema de Eventos.

## Delivery Strategy

- Implementacion secuencial en `main`.
- Cada slice termina en un commit verificable.
- Primero la base compartida de consultas; despues organizer; despues admin/plataforma.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Reporting model | Shared read layer plus viewmodels por scope | Un `ReportGenerator` monolitico | Reduce acoplamiento y mantiene cada reporte facil de razonar. |
| Report scopes | Organizer y platform/admin separados | Un solo centro con toggle interno | Los permisos y la carga cognitiva son distintos; mejor separar surfaces. |
| Data source | Reusar importes exactos de facturacion/payouts y agregados por organizador/evento | Recalcular desde datos legacy | Evita deriva monetaria y hace deterministas los reportes. |
| Export format | CSV en la primera iteracion | PDF/XLSX desde el inicio | CSV cubre el caso operativo sin introducir nuevas dependencias. |
| UI pattern | KPI cards + table + banner + empty state | Solo tablas planas | Sigue el patron de HI.EVENTS y mejora la lectura rapida. |

## Report Contracts

| Scope | Default filters | Primary cards | Table focus |
|---|---|---|---|
| Organizer | 90 days, all currencies, current organizer, optional event | Ingresos, impuestos, tarifas, payouts, rendimiento | Totals by date/event/currency |
| Platform | 90 days, all currencies, selected organizer or all | Global revenue, organizer breakdown, taxes, fees, payouts | Totals by organizer/date/currency |

Each report page SHOULD start with a section selector or landing cards and SHOULD keep the CSV export action visible above the fold.

## Data Flow

`Report request` -> controller fino -> viewmodel por scope -> shared query/aggregation layer -> tabla/cards -> export CSV

`Admin organizer filter` -> shared query/aggregation layer -> scope global -> KPI cards/tabla -> CSV

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/ViewModels/Reports/*` | Create | ViewModels de organizer y platform. |
| `app/Services/Reports/*` | Create | Consultas compartidas y agregacion. |
| `app/Http/Controllers/*Reports*.php` | Create/Modify | Entrypoints de lectura por scope. |
| `resources/views/livewire/*reports*.blade.php` | Create | Pantallas de reportes y export. |
| `routes/web.php` | Modify | Rutas de organizer y admin. |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Agregaciones y filtros | Tests directos a servicios/query helpers. |
| Feature | Permisos por scope y contenido renderizado | Tests con DB real y requests autenticadas. |
| Feature | Export CSV | Assert de respuesta y contenido basico. |

## Migration / Rollout

Ship the shared reporting foundation first, then the organizer surface, then the platform surface. Keep reporting read-only so the rollout does not affect facturacion or payouts.

## Open Questions

- PDF/XLSX se dejan fuera de 4.3 a menos que se apruebe una dependencia adicional.
- Si se necesita un dashboard global adicional, se define como parte del scope de platform y no como una pantalla separada.
- Event drilldown, if needed, stays read-only and inside the organizer surface.
