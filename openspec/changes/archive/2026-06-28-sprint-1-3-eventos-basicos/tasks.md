# Tasks: Sprint 1.3 — Eventos Básicos

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 900-1,500 |
| 400-line budget risk | High |
| Chained PRs recommended | No; solo contributor, size exception accepted |
| Suggested split | Single implementation branch with work-unit commits: data model → use cases → HTTP/auth → UI → integration/closure |
| Delivery strategy | exception-ok |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Commit Unit | Notes |
|------|------|-----------|-------|
| 1 | Base de datos y modelos del agregado | Commit 1 | Migraciones, enums, modelos, factories y relaciones. |
| 2 | Casos de uso y validación | Commit 2 | DTOs, FormRequests y Actions con TDD RED/GREEN/REFACTOR. |
| 3 | HTTP, policies y rutas | Commit 3 | Acceso organizer/admin y respuestas HTML. |
| 4 | UI interna y filtros | Commit 4 | Listas, formularios y acciones publish/cancel. |
| 5 | Cierre y verificación | Commit 5 | QA, docs y ajustes finales. |

## Phase 1: Foundation / Data Model

- [x] 1.1 RED: añadir tests de migración/relaciones/factories para `category`, `venue` y `event` en `tests/Feature/` y `tests/Unit/`.
- [x] 1.2 Crear migraciones en `database/migrations/` para `category`, `venue` y `event` con PK/FK singulares, soft deletes e índices de `organizer_id`, `slug` y `status`.
- [x] 1.3 Crear `app/Enums/EventStatus.php` y `app/Enums/EventVisibility.php`, más `app/Models/{Category,Venue,Event}.php` y factories.

## Phase 2: Core Implementation / Use Cases

- [x] 2.1 RED: cubrir creación/edición/publicación/cancelación/sanitización en `tests/Feature/Events/*Test.php`.
- [x] 2.2 Crear `app/DataTransferObjects/Events/*Dto.php` y `app/Http/Requests/Events/*Request.php` con `toDto()`.
- [x] 2.3 Implementar `app/Actions/Events/{Create,Update,Publish,Pause,Cancel}EventAction.php` usando Purifier y reglas de transición.

## Phase 3: Integration / HTTP and Authorization

- [x] 3.1 RED: añadir tests de policy y acceso cruzado entre organizers para `viewer`, `editor`, `admin` y global admin.
- [x] 3.2 Crear `app/Policies/EventPolicy.php` y `VenuePolicy.php`, usando autodiscovery de policies de Laravel 12 o registro equivalente solo si el repo lo requiere.
- [x] 3.3 Crear `app/Http/Controllers/Organizers/EventController.php`, rutas anidadas en `routes/web.php` y resolución de organizer por `organizer.detect`.

## Phase 4: UI / Internal Experience

- [x] 4.1 RED: cubrir render y navegación de listas/formularios/detalle en `tests/Feature/Events/EventUiTest.php`.
- [x] 4.2 Crear/ajustar vistas Blade en `resources/views/organizers/events/` y `resources/views/organizers/venues/` reutilizando `layouts.app` y componentes `x-form.*`.
- [x] 4.3 Añadir filtros básicos de listado: estado, visibilidad, rango de fechas y búsqueda simple; enlazar acciones publish/cancel desde el detalle.

## Phase 5: Testing / Integration / Closure

- [x] 5.1 Ejecutar y ajustar tests de migración, acciones, policies y UI hasta cubrir los escenarios de specs `category-taxonomy`, `venue-management`, `event-management`, `event-lifecycle` y `event-authorization`.
- [x] 5.2 Verificar que `DatabaseSeeder` incluya la taxonomía inicial de `Category` si la spec la requiere; si no, documentar la decisión en el cambio.
- [x] 5.3 Revisar naming, imports, `declare(strict_types=1)` y limpieza final; preparar el cambio para `sdd-apply` y posterior `sdd-verify`.

## Phase 6: Verification Follow-up

- [x] 6.1 Implementar gestión interna de venues (`VenueController`, rutas, FormRequests, DTOs, Actions y vistas mínimas) para cerrar los escenarios de `venue-management`.
- [x] 6.2 Añadir cobertura HTTP/UI para crear, editar y listar venues con aislamiento por organizer.
- [x] 6.3 Re-ejecutar verificación SDD y QA local tras cerrar los warnings.
