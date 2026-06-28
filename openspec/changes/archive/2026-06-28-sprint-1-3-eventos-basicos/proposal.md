# Proposal: Sprint 1.3 — Eventos Basicos

## Intent

Introducir el primer agregado central de negocio (`Event`) para que un organizer gestione eventos propios desde el panel interno, sin mezclar todavía ticketing, checkout ni catálogo público.

## Scope

### In Scope
- Taxonomía básica global de categorías con jerarquía simple.
- Venues reutilizables y aislados por organizer.
- Eventos propios del organizer con estado, visibilidad, fechas, descripción sanitizada, categoría y venue.
- Acciones internas para crear, editar, publicar, pausar/cancelar y listar eventos con filtros básicos.
- Policies por rol de organizer (`admin`, `editor`, `viewer`) y acceso de global admin.

### Out of Scope
- Entradas, precios, stock, checkout, pagos, tickets, QR, asistentes y check-in.
- Catálogo público SEO, recurrencia avanzada, mapas externos y nuevo layout de organizer.

## Capabilities

### New Capabilities
- `category-taxonomy`: categorías globales jerárquicas para clasificar eventos.
- `venue-management`: venues reutilizables, propiedad de un organizer y aislados entre organizers.
- `event-management`: CRUD interno de eventos, filtros, relaciones, sanitización y auditoría.
- `event-lifecycle`: estados, visibilidad y transiciones permitidas para publicar, pausar, cancelar y preparar eventos.
- `event-authorization`: permisos de lectura/escritura por rol dentro del organizer y acceso global admin.

### Modified Capabilities
- None; no active `openspec/specs/` capability specs are present.

## Approach

Seguir la arquitectura existente: migraciones singulares, modelos finales con factories, enums `EventStatus`/`EventVisibility`, DTOs, FormRequests con `toDto()`, Actions invocables, Controller fino, Policy, Resources/ViewModels si la lectura combina datos, UI Volt/Blade reutilizando el admin layout y cobertura Pest por capa antes de implementación.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | Tablas `category`, `venue`, `event`. |
| `app/Models/`, `app/Enums/` | New | Modelos, relaciones, casts, estados y visibilidad. |
| `app/Actions/`, `app/DataTransferObjects/`, `app/Http/Requests/` | New | Casos de uso y entrada validada. |
| `app/Http/Controllers/`, `app/Policies/`, `routes/web.php` | New/Modified | Endpoints internos, autorización y rutas nombradas. |
| `resources/views/`, `tests/Feature/`, `tests/Unit/` | New/Modified | UI interna y pruebas. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Mezclar alcance con ticketing | Med | Specs separadas y tests solo de eventos básicos. |
| Fuga entre organizers | High | Constraints, policies y tests cross-organizer. |
| Publicar eventos incompletos | Med | `PublishEventAction` valida mínimos y transiciones. |

## Rollback Plan

Revertir commits del cambio: eliminar rutas, UI, actions, requests, DTOs, policies, modelos/enums, tests y migraciones nuevas. Si las migraciones llegaron a ejecutarse en un entorno no productivo, aplicar `down()` en orden inverso.

## Dependencies

- Sprint 1.2 implementado: organizer, roles de organizer, admin layout, auditoría y autenticación.

## Proposal question round

- Asumido: categorías globales inicialmente; falta decidir si nacen por seeder o gestión global.
- Asumido: venues/eventos pertenecen al organizer; no hay catálogo público ni checkout.
- Pendiente: confirmar campos mínimos y copy del formulario inicial.

## Success Criteria

- [ ] Organizer crea, edita, publica y cancela eventos propios.
- [ ] `viewer` solo lee; otro organizer no accede.
- [ ] Global admin puede auditar/gestionar según policy.
- [ ] Descripciones HTML se sanitizan y QA pasa limpio.
