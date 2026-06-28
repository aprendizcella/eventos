# Design: Sprint 1.3 — Eventos Basicos

## Technical Approach

Implementar el primer agregado `Event` sobre la base de Sprint 1.2: datos en tablas singulares, modelos Eloquent con factories, enums para ciclo de vida, DTOs/FormRequests para entrada, Actions para escritura, controllers finos y Blade reutilizando `layouts.app`. Las lecturas se mantendrán server-rendered con queries paginadas y filtros; no se introduce layout nuevo de organizer ni ticketing.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Organizer scope | Rutas anidadas `organizers/{organizer}/events` y `organizers/{organizer}/venues` con `organizer.detect` | Resolver solo desde sesión | La ruta hace explícito el scope y reutiliza `DetectCurrentOrganizer`/`User::currentOrganizer()` ya existentes. |
| Taxonomía | `Category` global, seeded e idempotente | Categorías por organizer o CRUD global ahora | La spec define datos de plataforma; seeded evita ampliar Sprint 1.3 con administración global. |
| Sanitización | Usar `mews/purifier` inyectado en Actions antes de persistir `description` | `strip_tags()` o sanitizar en Blade | Ya existe la dependencia; persistir HTML limpio evita XSS en cualquier futura salida. |
| Lifecycle | Actions separadas `PublishEventAction`, `PauseEventAction`, `CancelEventAction` | Mutar estado dentro del controller | Centraliza reglas de transición, validación mínima y activity log. |
| UI | Blade clásico con componentes `x-form.*` y `x-ui.button` | Volt CRUD o nuevo organizer layout | Organizer CRUD actual usa Blade; Sprint 1.4 decidirá layout dedicado. |

## Data Flow

```text
Browser → Route organizer scope → Controller → Policy
        → FormRequest::toDto() → Action → Purifier/Transition rules → Model
        → Activity log → Redirect/View

List: Controller → scoped Event/Venue query + filters → Blade table/form
```

## File Changes

| File | Action | Description |
|---|---|---|
| `database/migrations/*_create_category_table.php` | Create | Global hierarchy: `category_id`, `parent_id`, `name`, `slug`, timestamps, soft deletes. |
| `database/migrations/*_create_venue_table.php` | Create | Organizer-owned venues with address, city, capacity, description, soft deletes. |
| `database/migrations/*_create_event_table.php` | Create | Organizer-owned events with slug, sanitized description, dates, status, visibility, category/venue FKs. |
| `database/seeders/CategorySeeder.php`, `DatabaseSeeder.php` | Create/Modify | Idempotent platform taxonomy seed. |
| `app/Models/{Category,Venue,Event}.php`, factories | Create | Relationships, casts, fillable, scopes, activity logging for Event. |
| `app/Enums/{EventStatus,EventVisibility}.php` | Create | Fixed lifecycle and visibility values. |
| `app/DataTransferObjects/{Events,Venues}/*Dto.php` | Create | Create/update/filter input contracts. |
| `app/Actions/{Events,Venues}/*.php` | Create | CRUD, filters and lifecycle transitions. |
| `app/Http/Requests/{Events,Venues}/*Request.php` | Create | Validation plus `toDto()`. |
| `app/Http/Controllers/Organizers/{EventController,VenueController}.php` | Create | Thin HTML controllers. |
| `app/Policies/{EventPolicy,VenuePolicy}.php` | Create | Global admin plus organizer role/ownership checks. |
| `routes/web.php` | Modify | Add nested event/venue routes under verified organizer scope. |
| `resources/views/organizers/{events,venues}/**.blade.php`, `components/navigation/sidebar.blade.php` | Create/Modify | Internal lists/forms/actions in current admin layout. |
| `tests/{Feature,Unit}/...` | Create | Migration, model, action, request, policy, integration and component coverage. |

## Interfaces / Contracts

```php
new CreateEventDto(
    organizerId: int,
    title: string,
    slug: string,
    description: ?string,
    startsAt: ?CarbonImmutable,
    endsAt: ?CarbonImmutable,
    categoryId: ?int,
    venueId: ?int,
    visibility: EventVisibility = EventVisibility::Private,
);
```

Policies expose `viewAny`, `view`, `create`, `update`, `publish`, `pause`, `cancel`; editors/admins can write inside their organizer, viewers read only, `super_admin`/`platform_admin` bypass organizer ownership.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Unit | Enums, lifecycle transition guards, purifier behavior, DTO construction | Pest unit tests with direct Action/model assertions. |
| Feature | Migrations, relationships, seeded categories, requests, policies, CRUD/filter routes, cross-organizer denial | Pest + `LazilyRefreshDatabase`, factories, role pivot setup mirroring organizer tests. |
| E2E | Not required for Sprint 1.3 | Covered by feature tests; browser tests deferred unless UI regressions appear. |

## Migration / Rollout

No destructive migration required. Run new migrations, then seed `CategorySeeder`. Existing organizers remain valid; events and venues start empty.

## Open Questions

- [ ] Confirm exact initial category names for `CategorySeeder`.
- [ ] Confirm minimum event form copy/field labels before implementation polish.
