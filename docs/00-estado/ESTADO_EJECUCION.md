# Estado de ejecución

> **Resumen en una línea:** Sprint 1.1 (Setup y Auth), Sprint 1.2 (Organizadores y Equipos), Account UX y Email Verification Gate están **implementados, verificados y cerrados**. La base UX Foundation (dark/light mode, layout admin) está **implementada**. El siguiente bloque recomendado es Sprint 1.3 (primeros flujos de evento) o el plan de implementación pendiente.

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

---

## Qué NO está hecho

- Sprint 1.3 — Primeros flujos de dominio de Event.
- Sprint 1.4 — Cierre de Fase 1 (integración end-to-end de Fundacion).
- Fases 2–6: ticketing, operación, monetización, discovery, administración.

El roadmap completo está en [`01-producto/PLAN_IMPLEMENTACION.md`](../01-producto/PLAN_IMPLEMENTACION.md).

---

## Bloqueos actuales

Ninguno conocido a cierre de UX Foundation.

---

## Próximo paso

- El siguiente bloque recomendado es continuar con **Sprint 1.3** (eventos) o, si se prefiere cerrar primero la línea de producto, abordar el sprint posterior de **onboarding de usuarios e invitaciones**.
- La creación asistida de usuarios asociados a organizer queda documentada para un sprint posterior.
