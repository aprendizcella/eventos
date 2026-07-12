# Proposal: Sprint 4.4 — Retro y Ajustes

## Intent

Cerrar la Fase 4 (Monetización) instalando observabilidad de colas con Horizon sobre Redis, segmentando jobs por prioridad operativa, y sincronizando el cierre documental del roadmap. No se altera lógica funcional de facturación, comisiones, payouts ni reportes.

## Scope

### In Scope

- **4.4a** — Horizon instalado, Redis como backend de colas productivas, gate de acceso (`super_admin`/`platform_admin`), arranque reproducible en Sail.
- **4.4b** — Segmentación de jobs en colas `tickets`, `emails`, `default`; jobs existentes (`SendTicketEmailJob`, `SendBulkEmailJob`) reasignados; link admin a Horizon en backoffice.
- **4.4c** — Fixes residuales detectados en fase; redacción de retro Fase 4 (`RETRO_FASE_4.md`); sincronización de `ESTADO_EJECUCION.md` y `PLAN_IMPLEMENTACION.md`.

### Out of Scope

- Cambios funcionales en facturación, comisiones, payouts o reportes.
- Stripe Connect, transferencias reales, onboarding KYC.
- Nuevas métricas de negocio o dashboards de producto.
- Cache Redis para lecturas (difiere a Fase 5).

## Capabilities

### New Capabilities

- `queue-observability`: Monitor operativo de colas vía Horizon, con Redis como backend, segmentación de jobs por prioridad, y acceso restringido a administradores globales.

### Modified Capabilities

None — no existing spec behavior changes. This is infrastructure and documentation only.

## Approach

1. **4.4a** — `composer require laravel/horizon`, publicar config/assets, configurar `QUEUE_CONNECTION=redis` en Sail, conectar `redis` driver en `config/queue.php`, definir gate en `AuthServiceProvider` (o `AppServiceProvider`), añadir supervisor en `docker/supervisord/` de Sail.
2. **4.4b** — Definir colas nominadas en `config/horizon.php` con `tickets` (alta), `emails` (media), `default` (baja); reasignar jobs a `->onQueue()`; añadir enlace condicional a Horizon en admin sidebar.
3. **4.4c** — Revisar issues abiertos, ejecutar QA completo, redactar retro, sincronizar documentos de estado y roadmap.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `composer.json` | Modified | Add `laravel/horizon` |
| `config/horizon.php` | New | Horizon configuration with named queues |
| `config/queue.php` | Modified | Add `redis` connection as default |
| `.env` / Sail docker | Modified | Redis config, supervisord worker |
| `routes/web.php` | Modified | Conditional Horizon route (gate-protected) |
| `resources/views/livewire/` | Modified | Optional link in admin sidebar |
| `docs/00-estado/` | Modified | Retro + sync estado + plan |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Redis no disponible en CI/testing | Low | `QUEUE_CONNECTION=sync` en testing; Horizon solo en local/prod |
| Horizon expone métricas sensibles | Low | Gate protege acceso; solo super_admin/platform_admin |
| Jobs no se migran a cola correcta | Low | Tests verifican `->onQueue()` en jobs afectados |

## Rollback Plan

1. Revertir `composer.json` y `config/` cambios de Horizon/Redis.
2. Volver a `QUEUE_CONNECTION=database` (o sync) en `.env`.
3. Eliminar supervisor config de Sail si se añadió.
4. Revertir rutas y enlaces de admin sidebar.
5. Ejecutar `composer qa` para verificar estado limpio.

## Dependencies

- Redis accesible en Sail (ya configurado en boilerplate).
- Spatie Laravel Permission (roles `super_admin`/`platform_admin` existen).
- Sprints 4.1, 4.2, 4.3 completados.

## Success Criteria

- [ ] Horizon dashboard accesible y protegido por gate.
- [ ] Backend de colas cambiado a Redis (productivo) sin afectar testing (sync).
- [ ] Jobs `SendTicketEmailJob` y `SendBulkEmailJob` en colas dedicadas.
- [ ] Enlace condicional a Horizon visible para admins.
- [ ] Retro Fase 4 redactada, estado del proyecto sincronizado.
- [ ] QA pipeline y SonarQube pasan limpios.
- [ ] Fase 4 lista para archivo.
