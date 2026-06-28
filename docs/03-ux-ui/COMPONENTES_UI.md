# Componentes UI

Estado actual de la carpeta de componentes Blade, Livewire Volt y criterio de reutilización.

> **En una línea:** las primitivas reutilizables viven en `components/form/` y `components/ui/`; las tablas interactivas de dominio viven como componentes Livewire Volt en `resources/views/livewire/organizers/`.

---

## 1. Estructura actual

```text
resources/views/
├── components/
│   ├── form/
│   │   ├── date.blade.php
│   │   ├── field.blade.php
│   │   ├── input.blade.php
│   │   └── password-input.blade.php
│   │   └── select.blade.php
│   ├── ui/
│   │   ├── button.blade.php
│   │   ├── link.blade.php
│   │   ├── modal.blade.php
│   │   ├── theme-init.blade.php
│   │   └── theme-toggle.blade.php
│   ├── layout/
│   │   └── app-shell.blade.php
│   └── navigation/
│       ├── sidebar.blade.php
│       └── topbar.blade.php
├── livewire/
│   ├── auth/
│   │   ├── forgot-password.blade.php
│   │   ├── login.blade.php
│   │   ├── register.blade.php
│   │   └── reset-password.blade.php
│   └── dashboard.blade.php
│   └── organizers/
│       ├── events-table.blade.php
│       ├── organizers-table.blade.php
│       ├── team-table.blade.php
│       └── venues-table.blade.php
└── layouts/
    ├── app.blade.php
    └── auth.blade.php
```

### Qué hace cada componente

| Componente | Responsabilidad real |
|---|---|
| `ui/button.blade.php` | Botón con estilo primario para formularios. |
| `ui/modal.blade.php` | Modal reutilizable con overlay, cierre por Escape/click exterior y scroll interno. |
| `form/input.blade.php` | Input de texto alineado con TailAdmin. |
| `form/select.blade.php` | Select reutilizable con label, error y soporte dark mode. |
| `form/date.blade.php` | Datepicker propio con Alpine y valor enviado en formato `Y-m-d`. |
| `form/field.blade.php` | Label + input + mensajes de error. |
| `ui/link.blade.php` | Enlace con estilo de texto secundario. |
| `form/password-input.blade.php` | Input de contraseña con toggle de visibilidad. |
| `ui/theme-init.blade.php` | Script inline para prevenir FOUC de tema (ejecuta antes de Alpine). |
| `ui/theme-toggle.blade.php` | Dropdown accesible para cambiar tema (light/dark/system) con Alpine.js. |
| `layout/app-shell.blade.php` | Estructura base del panel admin (sidebar + topbar + main). |
| `navigation/sidebar.blade.php` | Sidebar con navegación principal. |
| `navigation/topbar.blade.php` | Topbar con theme toggle y menú de usuario. |
| `livewire/organizers/*-table.blade.php` | Tablas reactivas de dominio con búsqueda, ordenación, paginación, columnas visibles y acciones autorizadas. |

---

## 2. Estructura y criterio de clasificación

```text
resources/views/components/
├── form/
│   ├── date.blade.php
│   ├── field.blade.php
│   ├── input.blade.php
│   └── password-input.blade.php
│   └── select.blade.php
├── ui/
│   ├── button.blade.php
│   ├── link.blade.php
│   ├── modal.blade.php
│   ├── theme-init.blade.php
│   └── theme-toggle.blade.php
├── layout/
│   └── app-shell.blade.php
└── navigation/
    ├── sidebar.blade.php
    └── topbar.blade.php
```

### Criterio de clasificación

- **`form/`** → todo lo que forma parte de un `<form>`: inputs, validación inline, password toggle.
- **`ui/`** → primitivas visuales que no pertenecen a un formulario: botones, links, badges, iconos, modales, tooltips, theme toggle.
- **`layout/`** → estructuras de layout reutilizables: app-shell, page-header, content-card.
- **`navigation/`** → componentes de navegación: sidebar, topbar, breadcrumbs.
- **`livewire/organizers/`** → componentes de dominio con estado servidor; no son primitivas genéricas.

---

## 3. Migración completada

Los componentes se movieron de `components/auth/` a `components/form/` y `components/ui/`. La carpeta `components/auth/` se eliminó.

### Uso actual en las vistas de auth

```blade
<x-form.field />
<x-form.password-input />
<x-ui.button />
<x-ui.link />
```

> **Nota:** migración empaquetada como commit `refactor:` al inicio del Sprint 1.2.

---

## 4. JavaScript interactivo — Alpine.js

El proyecto usa **Alpine.js** para interacciones locales de UI (dropdowns, toggles, estado visual de componentes). Desde la adopción de Livewire 4, Alpine se carga mediante `@livewireScripts`.

- **Instalación:** `npm install alpinejs` (en `dependencies`).
- **Inicialización:** `resources/js/app.js` no debe importar ni arrancar Alpine manualmente; Livewire 4 lo carga automáticamente.
- **Uso en Blade:** directivas `x-data`, `x-show`, `@click`, `:class`, etc. directamente en el HTML.
- **FOUC de tema:** el script inline `theme-init.blade.php` corre antes de Alpine para aplicar la clase `dark` antes del primer paint.

### Componentes que usan Alpine

| Componente | Estado Alpine |
|---|---|
| `ui/theme-toggle.blade.php` | `x-data` con `theme`, `open`, `setTheme()`, `applyTheme()`. Dropdown reactivo, persistencia en `localStorage`, soporte `prefers-color-scheme`. |
| `layout/app-shell.blade.php` | `x-data="{ sidebarOpen: false }"` — estado compartido entre topbar y sidebar. |
| `navigation/sidebar.blade.php` | `:class` reactivo para `-translate-x-full`, overlay con `x-show="sidebarOpen"`. |
| `navigation/topbar.blade.php` | Botón toggle con `@click="sidebarOpen = !sidebarOpen"`. |
| `form/date.blade.php` | Estado del datepicker, mes visible, día seleccionado y auto-posicionamiento. |
| `ui/modal.blade.php` | Visibilidad, cierre por Escape/click exterior y transiciones. |
| `livewire/organizers/*-table.blade.php` | Dropdowns de columnas/filtros dentro de componentes Livewire. |

### Convenciones

- Estado reactivo cerca de donde se usa (componente-scoped), no stores globales salvo necesidad real.
- `theme-init.blade.php` sigue siendo inline para prevenir FOUC — Alpine corre después.
- No duplicar listeners vanilla si Alpine maneja el estado.
- No arrancar Alpine manualmente si Livewire ya está presente; duplicarlo puede romper componentes interactivos.

---

## 5. Tablas Livewire Volt

La estrategia vigente para tablas administrativas es **componente Volt por dominio**, no una primitiva `<x-ui.table>` genérica.

| Componente | Uso |
|---|---|
| `organizers.organizers-table` | Índice global de organizers. |
| `organizers.team-table` | Gestión de miembros del organizer. |
| `organizers.events-table` | Listado de eventos con filtros por status, visibility y fechas. |
| `organizers.venues-table` | Listado de venues del organizer. |

Reglas:

- Toda acción pública de escritura debe autorizar en servidor con policy/gate.
- Los registros nested se resuelven desde el organizer montado antes de operar sobre ellos.
- La visibilidad con `@can` mejora la UX, pero nunca reemplaza la autorización del método Livewire.
- Reutilizar estilos TailAdmin, pero mantener la lógica de dominio explícita dentro de cada tabla.

---

## 6. Convenciones de autoría

- Props tipadas cuando sea posible (`@props(['variant' => 'primary'])`).
- Slots con nombre solo cuando haya más de uno.
- Clases de Tailwind compuestas con `@class([...])` o `merge`.
- Documentar el componente con un comentario breve al inicio del archivo si tiene variantes o estados no obvios.

---

## Documentos relacionados

- [`DECISIONES_UX.md`](./DECISIONES_UX.md) — filosofía general y elección TailAdmin/Materio.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — qué tomar de TailAdmin y qué descartar.
