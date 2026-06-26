# Componentes UI

Estado actual de la carpeta de componentes Blade y objetivo de reorganización.

> **En una línea:** los componentes reutilizables viven en `components/form/` (primitivas de formulario) y `components/ui/` (primitivas visuales genéricas). La antigua carpeta `components/auth/` se eliminó tras la migración.

---

## 1. Estructura actual

```text
resources/views/
├── components/
│   ├── form/
│   │   ├── field.blade.php
│   │   └── password-input.blade.php
│   ├── ui/
│   │   ├── button.blade.php
│   │   ├── link.blade.php
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
└── layouts/
    ├── app.blade.php
    └── auth.blade.php
```

### Qué hace cada componente

| Componente | Responsabilidad real |
|---|---|
| `ui/button.blade.php` | Botón con estilo primario para formularios. |
| `form/field.blade.php` | Label + input + mensajes de error. |
| `ui/link.blade.php` | Enlace con estilo de texto secundario. |
| `form/password-input.blade.php` | Input de contraseña con toggle de visibilidad. |
| `ui/theme-init.blade.php` | Script inline para prevenir FOUC de tema (ejecuta antes de Alpine). |
| `ui/theme-toggle.blade.php` | Dropdown accesible para cambiar tema (light/dark/system) con Alpine.js. |
| `layout/app-shell.blade.php` | Estructura base del panel admin (sidebar + topbar + main). |
| `navigation/sidebar.blade.php` | Sidebar con navegación principal. |
| `navigation/topbar.blade.php` | Topbar con theme toggle y menú de usuario. |

---

## 2. Estructura y criterio de clasificación

```text
resources/views/components/
├── form/
│   ├── field.blade.php
│   └── password-input.blade.php
├── ui/
│   ├── button.blade.php
│   ├── link.blade.php
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

El proyecto usa **Alpine.js** como librería reactiva para interacciones de UI (dropdowns, toggles, estado de componentes).

- **Instalación:** `npm install alpinejs` (en `dependencies`).
- **Inicialización:** `resources/js/app.js` importa Alpine, lo expone en `window.Alpine` y llama `Alpine.start()`.
- **Uso en Blade:** directivas `x-data`, `x-show`, `@click`, `:class`, etc. directamente en el HTML.
- **FOUC de tema:** el script inline `theme-init.blade.php` corre antes de Alpine para aplicar la clase `dark` antes del primer paint.

### Componentes que usan Alpine

| Componente | Estado Alpine |
|---|---|
| `ui/theme-toggle.blade.php` | `x-data` con `theme`, `open`, `setTheme()`, `applyTheme()`. Dropdown reactivo, persistencia en `localStorage`, soporte `prefers-color-scheme`. |
| `layout/app-shell.blade.php` | `x-data="{ sidebarOpen: false }"` — estado compartido entre topbar y sidebar. |
| `navigation/sidebar.blade.php` | `:class` reactivo para `-translate-x-full`, overlay con `x-show="sidebarOpen"`. |
| `navigation/topbar.blade.php` | Botón toggle con `@click="sidebarOpen = !sidebarOpen"`. |

### Convenciones

- Estado reactivo cerca de donde se usa (componente-scoped), no stores globales salvo necesidad real.
- `theme-init.blade.php` sigue siendo inline para prevenir FOUC — Alpine corre después.
- No duplicar listeners vanilla si Alpine maneja el estado.

---

## 5. Convenciones de autoría

- Props tipadas cuando sea posible (`@props(['variant' => 'primary'])`).
- Slots con nombre solo cuando haya más de uno.
- Clases de Tailwind compuestas con `@class([...])` o `merge`.
- Documentar el componente con un comentario breve al inicio del archivo si tiene variantes o estados no obvios.

---

## Documentos relacionados

- [`DECISIONES_UX.md`](./DECISIONES_UX.md) — filosofía general y elección TailAdmin/Materio.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — qué tomar de TailAdmin y qué descartar.
