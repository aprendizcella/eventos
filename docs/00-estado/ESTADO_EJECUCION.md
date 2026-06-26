# Estado de ejecución

> **Resumen en una línea:** Sprint 1.1 (Setup y Auth) está **implementado, verificado y cerrado**. El stack base de Fase 1 está operativo. El siguiente bloque de trabajo es Sprint 1.2 (primeros flujos de dominio: Event / Organizer).

---

## Qué está hecho

### Sprint 1.1 — Setup y Auth ✅

- **Stack base instalado y operativo:** Laravel 12 / PHP 8.4 / Sail, Sanctum, Spatie Permission, Spatie Activitylog, Mews/Purifier, Livewire + Volt.
- **Auth flows funcionales:** registro, login, logout, reset de password, verificación de email (`MustVerifyEmail`).
- **Roles y permisos:** Spatie Permission con `RoleSeeder` (seis roles base).
- **Trazabilidad:** Spatie Activitylog activo sobre `User` y entidades relevantes.
- **Migraciones y seeders:** `permission_tables`, `activity_log`, `personal_access_tokens` desplegados.
- **Tests en verde** y pipeline QA (`composer qa`: rector → pint → phpstan → tests) limpio.

### Refactor de componentes Auth (UI)

- Componentes Blade de auth reorganizados bajo `resources/views/components/auth/`:
  - `button.blade.php`
  - `field.blade.php`
  - `link.blade.php`
  - `password-input.blade.php`
- Páginas Livewire/Volt de auth bajo `resources/views/livewire/auth/`:
  - `login.blade.php`
  - `register.blade.php`
  - `forgot-password.blade.php`
  - `reset-password.blade.php`
- El refactor deja la base limpia para **generalizar los componentes** hacia `components/form/` y `components/ui/` (ver [`03-ux-ui/COMPONENTES_UI.md`](../03-ux-ui/COMPONENTES_UI.md)).

---

## Qué NO está hecho

- Sprint 1.2 — Primeros flujos de dominio (Event / Organizer).
- Sprint 1.3 — Modelos y acciones de Product / Ticket.
- Sprint 1.4 — Cierre de Fase 1 (integración end-to-end de Fundacion).
- Fases 2–6: ticketing, operación, monetización, discovery, administración.

El roadmap completo está en [`01-producto/PLAN_IMPLEMENTACION.md`](../01-producto/PLAN_IMPLEMENTACION.md).

---

## Bloqueos actuales

Ninguno conocido a cierre de Sprint 1.1.

---

## Próximo paso

Arrancar Sprint 1.2: definir spec (`sdd-spec`) y design (`sdd-design`) de los primeros casos de uso de dominio (crear/editar evento, crear organizer).
