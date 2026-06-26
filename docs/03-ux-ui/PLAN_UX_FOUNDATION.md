# Plan UX Foundation

Este documento define el orden de trabajo para consolidar la base UX/UI del proyecto antes de seguir con nuevas pantallas de dominio.

> **Decisión actual:** antes de iniciar Sprint 1.2 de Organizadores/Eventos, se realizará un bloque corto de cimentación UX para evitar duplicación visual, componentes dispersos y deuda de interfaz.

---

## 1. Objetivo

Crear una base visual reutilizable para formularios, layout de panel, navegación y modo claro/oscuro.

Esto permitirá que las próximas pantallas de administración y organizadores se construyan sobre componentes consistentes, no copiando clases Tailwind pantalla por pantalla.

---

## 2. Orden recomendado

| Orden | Trabajo | Resultado esperado |
|---|---|---|
| 1 | Commit limpio del estado actual | Punto de restauración antes de nuevos refactors. |
| 2 | ~~Mover componentes genéricos fuera de `components/auth/`~~ | ✅ Hecho. `components/form/` y `components/ui/` como base reutilizable. |
| 3 | Formalizar decisiones UX | `DECISIONES_UX.md`, `COMPONENTES_UI.md` y este plan actualizados. |
| 4 | ~~Implementar dark/light mode real~~ | ✅ Hecho. Toggle reutilizable con `light`, `dark` y `system`, persistencia en `localStorage`, clase `dark` en `documentElement`. |
| 5 | ~~Crear layout base de panel admin~~ | ✅ Hecho. Sidebar, topbar, main content slot, responsive. |
| 6 | Crear componentes mínimos de panel | Cards, page header, table shell, empty state, action buttons. |
| 7 | Empezar Sprint 1.2 | Organizadores/Eventos ya sobre la base UX. |

---

## 3. Trabajo 1 — Commit limpio del estado actual

Antes de seguir, debe existir un commit limpio con lo que ya está estable:

- correcciones UX de auth;
- refactor inicial de componentes;
- reorganización de documentación;
- índice `docs/README.md`;
- referencia ligera desde `AGENTS.md`.

**No incluir:**

- `.atl/`;
- `.gitignore` si no corresponde al cambio;
- `openspec/config.yaml` si no forma parte del commit;
- `build/`;
- `package-lock.json`;
- `opencode.json`;
- archivos temporales.

---

## 4. Trabajo 2 — Migrar componentes genéricos

### Estado actual (actualizado)

Los componentes se han movido a:

```txt
resources/views/components/
├── form/
│   ├── field.blade.php
│   └── password-input.blade.php
└── ui/
    ├── button.blade.php
    └── link.blade.php
```

La carpeta `components/auth/` se eliminó. Las vistas de auth usan `<x-form.*>` y `<x-ui.*>`.

### Objetivo (completado)

Componentes movidos a:

```txt
resources/views/components/
├── form/
│   ├── field.blade.php
│   └── password-input.blade.php
└── ui/
    ├── button.blade.php
    └── link.blade.php
```

### Uso esperado

```blade
<x-form.field />
<x-form.password-input />
<x-ui.button />
<x-ui.link />
```

### Verificación mínima

```bash
vendor/bin/sail artisan test --compact --filter=AuthUi
vendor/bin/sail artisan test --compact tests/Feature/Auth/RegisterTest.php
```

---

## 5. Trabajo 3 — Decisiones UX vivas

Las decisiones UX deben quedar documentadas en:

- [`DECISIONES_UX.md`](./DECISIONES_UX.md)
- [`COMPONENTES_UI.md`](./COMPONENTES_UI.md)
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md)
- este documento

Regla: **si una decisión UX no está documentada, no existe como estándar del proyecto.**

---

## 6. Trabajo 4 — Dark/light mode real

### Estado actual

✅ Implementado. Toggle reutilizable con soporte para `light`, `dark` y `system`.

### Implementación

- `resources/views/components/ui/theme-init.blade.php` — script inline para prevenir FOUC (corre antes de Alpine).
- `resources/views/components/ui/theme-toggle.blade.php` — dropdown accesible con 3 opciones, manejado por Alpine.js (`x-data` con `theme`, `open`, `setTheme()`, `applyTheme()`).
- `resources/js/app.js` — importa y arranca Alpine.js.
- `resources/css/app.css` — `@custom-variant dark (&:where(.dark, .dark *));` para modo oscuro basado en clase.
- `resources/js/theme.js` — eliminado; Alpine.js maneja ahora la lógica de tema.

### Criterio de aceptación

- ✅ El usuario puede cambiar tema desde UI.
- ✅ La preferencia sobrevive al refresh.
- ✅ `system` respeta `prefers-color-scheme`.
- ✅ El componente se reutiliza en auth layout y dashboard.

---

## 7. Trabajo 5 — Layout base de panel admin

### Estado actual

✅ Implementado. Layout base de panel admin con sidebar, topbar y main content slot.

### Componentes creados

```txt
resources/views/components/layout/
└── app-shell.blade.php

resources/views/components/navigation/
├── sidebar.blade.php
└── topbar.blade.php

resources/views/layouts/
└── app.blade.php
```

### Implementación

- `layouts/app.blade.php` — layout principal del panel admin.
- `layout/app-shell.blade.php` — estructura: sidebar + topbar + main.
- `navigation/sidebar.blade.php` — sidebar con links de navegación (Dashboard, Events, Organizers).
- `navigation/topbar.blade.php` — topbar con toggle de sidebar (mobile) y theme toggle.
- Responsive: sidebar oculto en mobile, visible en lg+. Toggle con overlay en mobile. Estado manejado por Alpine.js (`x-data="{ sidebarOpen: false }"` en `app-shell`).

### Dashboard placeholder

- `resources/views/livewire/dashboard.blade.php` — página Volt mínima para verificar el layout.
- `routes/web.php` — ruta `/dashboard` protegida con middleware `auth`.

### Decisiones tomadas

| Tema | Decisión |
|---|---|
| Sidebar | Fijo en desktop (lg+), oculto en mobile con toggle. |
| Topbar | Incluye theme toggle y placeholder para menú de usuario. |
| Breadcrumbs | No incluidos aún. Se agregarán cuando haya navegación profunda. |
| Densidad visual | TailAdmin como referencia: limpio, SaaS, profesional. |

---

## 8. Trabajo 6 — Componentes mínimos de panel

Crear solo cuando exista uso real:

```txt
resources/views/components/ui/
├── badge.blade.php
├── empty-state.blade.php
├── modal.blade.php
└── dropdown.blade.php

resources/views/components/table/
├── table.blade.php
├── header.blade.php
├── row.blade.php
└── actions.blade.php
```

No copiar nombres tipo `basic-tables-one`, `basic-tables-two`, etc. Eso es nomenclatura de plantilla/demo, no de producto.

---

## 9. Referencias visuales

### TailAdmin

Se usa como referencia principal para:

- organización visual de dashboards;
- densidad de información;
- tratamiento de formularios;
- tablas;
- cards;
- dark mode;
- layout SaaS/admin.

No se copia entero.

### Materio

Se mantiene como referencia secundaria.

No se adopta como base por ahora porque introducir Bootstrap junto a Tailwind aumentaría deuda y fricción visual.

---

## 10. Regla de avance

No empezar Sprint 1.2 de dominio hasta completar, como mínimo:

- [ ] commit limpio del estado actual;
- [x] componentes movidos a `form/` y `ui/`;
- [x] decisión de dark/light mode;
- [x] layout admin base definido o implementado.

---

## 11. Próximo paso inmediato

~~Ejecutar el mini-refactor:~~ ✅ Hecho.

```txt
components/auth/ → components/form/ + components/ui/
```

Después, crear el primer slice UX del panel admin.
