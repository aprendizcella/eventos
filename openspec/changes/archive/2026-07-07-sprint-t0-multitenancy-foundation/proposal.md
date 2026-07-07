# Proposal: Sprint T0 — Multitenancy Foundation

## Intent

Cerrar la base SaaS tenant-aware antes de facturación: usar `spatie/laravel-multitenancy` con una sola BBDD, resolver el tenant por dominio/host sin romper el flujo actual por ruta, y dejar jobs/listeners preparados para contexto de organizer.

## Scope

### In Scope
- Integrar `spatie/laravel-multitenancy` en modo single database.
- Usar `Organizer` como tenant y `Organizer.domain` como señal de branding/routing.
- Resolver tenant por el host raíz configurado por `APP_URL` y usar fallback por ruta solo para el panel interno `organizers/{organizer}`.
- Mantener el dominio raíz de cada entorno como contexto superadmin sin tenant activo.
- Hacer tenant-aware jobs/listeners/queues y añadir cobertura de aislamiento.

### Out of Scope
- Separación física de datos por base de datos.
- Reescritura completa del panel o de los módulos de negocio.
- Billing/facturación (queda para Sprint 4.1).

## Capabilities

### New Capabilities
- `tenant-context`: resolución de tenant, aislamiento por organizer y contexto de request.
- `tenant-aware-jobs`: colas, listeners y tareas que restauran el tenant correcto.

### Modified Capabilities
- None.

## Approach

Mantener una sola BBDD. Añadir el paquete, adaptar `Organizer` como tenant, introducir un `TenantFinder` propio con prioridad de host y fallback de ruta interna, y conservar `organizer.detect` solo como compatibilidad de transición para el panel existente. El dominio raíz configurado por `APP_URL` debe quedar como contexto superadmin sin tenant; la sesión no debe decidir el tenant.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `composer.json` | Modified | Add Spatie multitenancy package. |
| `config/multitenancy.php` | New | Package configuration for single DB tenant-aware mode. |
| `app/Models/Organizer.php` | Modified | Implement tenant contract/trait. |
| `app/Support/Multitenancy/` | New | Custom tenant finder / helpers. |
| `bootstrap/app.php` | Modified | Register multitenancy middleware/hooks. |
| `routes/web.php`, `routes/api.php` | Modified | Only if a route group needs explicit host-aware tenant bootstrap; avoid broad route rewrites. |
| `tests/Feature/Organizers/` | Modified/New | Isolation and domain-resolution coverage. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Host vs route precedence leaks the wrong tenant | High | Make host authoritative for custom domains and allow route fallback only on internal organizer URLs. |
| Job context lost in async flow | Med | Tenant-aware queue defaults and explicit tests. |
| Mixing tenancy with billing increases blast radius | High | Keep Sprint T0 separate from Sprint 4.1. |

## Rollback Plan

Remove the package config, custom tenant finder, and multitenancy hooks; keep `organizer.detect` and the single DB model unchanged. No data split or migration rollback is required.

## Dependencies

- Current single-database tenant-aware model (`organizer_id` scope) already verified.
- `Organizer.domain` exists and can be used as host/routing signal.

## Success Criteria

- [ ] A request resolves the correct tenant by host or organizer route consistently.
- [ ] Tenant-aware jobs/restored listeners keep organizer context.
- [ ] Cross-organizer access remains blocked.
- [ ] Sprint 4.1 can start with a stable tenant foundation.
