# Estado de ejecución

> **Resumen en una línea:** Sprint 1.1 (Setup y Auth), Sprint 1.2 (Organizadores y Equipos), Sprint 1.3 (Eventos Básicos), Sprint 1.4 (Panel de Organizador), Account UX, Email Verification Gate y la base UX Foundation están **implementados y verificados localmente**. El próximo paso es iniciar la Fase 2 con el Sprint 2.1 (Productos y Tipos de Entrada).

---

## Qué está hecho

### Sprint 1.1 — Setup y Auth ✅

- **Stack base instalado y operativo:** Laravel 12 / PHP 8.4 / Sail, Sanctum, Spatie Permission, Spatie Activitylog, Mews/Purifier, Livewire + Volt.
- **Auth flows funcionales:** registro, login, logout, reset de password, verificación de email (`MustVerifyEmail`).
- **Roles y permisos:** Spatie Permission con `RoleSeeder` (seis roles base).
- **Trazabilidad:** Spatie Activitylog activo sobre `User` y entidades relevantes.
- **Migraciones y seeders:** `permission_tables`, `activity_log`, `personal_access_tokens` desplegados.
- **Tests en verde** y pipeline QA (`composer qa`: rector → pint → phpstan → tests) limpio.
- **Checks confirmados post-merge:** login/logout/reset, email verification gate, account profile/password, team management y organizer CRUD.

### Refactor de componentes Auth (UI)

- Componentes Blade genéricos reorganizados bajo `resources/views/components/`:
  - `form/field.blade.php`
  - `form/password-input.blade.php`
  - `ui/button.blade.php`
  - `ui/link.blade.php`
- La antigua carpeta `components/auth/` se eliminó tras la migración.
- Páginas Livewire/Volt de auth bajo `resources/views/livewire/auth/`:
  - `login.blade.php`
  - `register.blade.php`
  - `forgot-password.blade.php`
  - `reset-password.blade.php`
- Las vistas de auth usan `<x-form.*>` y `<x-ui.*>` (ver [`03-ux-ui/COMPONENTES_UI.md`](../03-ux-ui/COMPONENTES_UI.md)).

### UX Foundation — Dark/Light Mode y Layout Admin ✅

- **Dark/light mode implementado:**
  - Soporte para `light`, `dark`, `system` con persistencia en `localStorage`.
  - Toggle reutilizable (`<x-ui.theme-toggle />`) en auth layout y admin layout, manejado por Alpine.js.
  - Script inline (`<x-ui.theme-init />`) para prevenir FOUC (corre antes de Alpine).
  - Clase `dark` aplicada en `documentElement` según tema activo.
  - `resources/css/app.css` configurado con `@custom-variant dark (&:where(.dark, .dark *))`.
  - `resources/js/theme.js` eliminado; Alpine.js maneja la lógica de tema.

- **Layout admin base implementado:**
  - `layouts/app.blade.php` — layout principal del panel admin.
  - `layout/app-shell.blade.php` — estructura: sidebar + topbar + main. Estado de sidebar en Alpine.js.
  - `navigation/sidebar.blade.php` — sidebar con navegación (Dashboard, Events, Organizers). Toggle reactivo con Alpine.
  - `navigation/topbar.blade.php` — topbar con theme toggle y menú de usuario.
  - Responsive: sidebar oculto en mobile, visible en lg+ con toggle. Estado manejado por Alpine.js (`x-data="{ sidebarOpen: false }"`).

- **Alpine.js integrado:**
  - Instalado vía `npm install alpinejs` (en `dependencies`).
  - Inicializado en `resources/js/app.js`.
  - Theme toggle y mobile sidebar migrados de vanilla JS a Alpine.js.
  - Vite build operativo (92KB JS bundle incluye Alpine).

- **Dashboard placeholder:**
  - `resources/views/livewire/dashboard.blade.php` — página Volt mínima.
  - Ruta `/dashboard` protegida con middleware `auth`.
  - Tests de contrato en `tests/Feature/AdminLayoutTest.php`.

### Sprint 1.2 — Organizadores y Equipos ✅

- CRUD de organizers implementado y verificado.
- Team management implementado y verificado.
- Roles del organizer modelados como catálogo propio (`admin`, `editor`, `viewer`) separado de Spatie.
- Policies y test coverage para global admins y organizer admins en verde.

### Sprint 1.3 — Eventos Básicos ✅

- Taxonomía global de categorías implementada con `CategorySeeder` idempotente.
- Venues y events modelados con tablas singulares, PK `{model}_id`, SoftDeletes, factories y relaciones con organizer.
- CRUD interno de eventos implementado con DTOs, FormRequests, Actions y controller fino.
- Ciclo de vida cubierto: draft/configured/published/paused/completed/cancelled, con publish/pause/cancel actions.
- Descripciones HTML se sanitizan con Purifier antes de persistir.
- Policies de events/venues cubren admin/editor/viewer, global admins y acceso cruzado entre organizers.
- UI interna Blade para listado, filtros, creación, edición, detalle y acciones publish/pause/cancel.
- `composer qa` pasa limpio: 415 tests, 1059 assertions.

### Account UX ✅

- Menú de usuario en topbar con nombre, rol, organizer, Profile y Sign out.
- Perfil editable para nombre; email visible pero read-only.
- Cambio de contraseña accesible desde profile y topbar.
- Remember me opt-in en login.

### Email Verification Gate ✅

- Usuarios no verificados bloqueados de dashboard, account y organizers.
- Notice / resend / callback / logout accesibles.
- Registro redirige a verificación.
- Seed/admin users pueden quedar pre-verificados.

### Sprint 1.4 — Panel de Organizador ✅

- **Estructura de Navegación Contextual:** Sidebar y switcher de tenant actualizados con ordenación determinista.
- **Dashboard Global del Organizador:** Métricas de negocio unificadas (Ventas y Registros como placeholders de diseño, Eventos Activos y Miembros del equipo reales), acompañado de un feed visual de **Pedidos Recientes**.
- **Panel de Ajustes Multitab (Alpine.js):** Pestañas independientes para Información Básica, Dirección, Redes Sociales, Valores Predeterminados y Zona de Peligro, con reautorización robusta en la mutación Livewire.
- **Sub-navegación del Evento:** Detalle de evento rediseñado con navegación interna por pestañas (Vista general, Entradas, Asistentes y Acciones de Ciclo de Vida) para consolidar los KPIs de forma limpia.
- **API REST básica:** Endpoints anidados `/api/v1/organizers/{organizer}` y `/api/v1/organizers/{organizer}/events` asegurados por Sanctum con contrato explícito (settings filtrado sin datos privados) y aislados por el middleware `organizer.detect`.
- **Paso limpio del pipeline de QA completo:** Suite de tests ejecutada en Pest de manera íntegra y sin filtros.

---

## Qué NO está hecho

- Fases 2–6: ticketing, operación, monetización, discovery, administración.

El roadmap completo está en [`01-producto/PLAN_IMPLEMENTACION.md`](../01-producto/PLAN_IMPLEMENTACION.md).

---

## Bloqueos actuales

Ninguno conocido a cierre de Sprint 1.4.

---

## Próximo paso

- El siguiente bloque recomendado es iniciar la **Fase 2: Ticketing y Compra**, comenzando por el **Sprint 2.1: Productos y Tipos de Entrada** (Semana 5).
- La creación asistida de usuarios asociados a organizer queda documentada para un sprint posterior.
