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
| 2 | Mover componentes genéricos fuera de `components/auth/` | `components/form/` y `components/ui/` como base reutilizable. |
| 3 | Formalizar decisiones UX | `DECISIONES_UX.md`, `COMPONENTES_UI.md` y este plan actualizados. |
| 4 | Implementar dark/light mode real | Toggle reutilizable con `light`, `dark` y `system`. |
| 5 | Crear layout base de panel admin | Sidebar/header/breadcrumbs/contenedor principal. |
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

### Estado actual

Los componentes creados durante el refactor de auth están bajo:

```txt
resources/views/components/auth/
├── field.blade.php
├── password-input.blade.php
├── button.blade.php
└── link.blade.php
```

Funcionan, pero su ubicación comunica mal la intención: no son componentes exclusivamente de autenticación.

### Objetivo

Moverlos a:

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

Existen clases `dark:*`, pero no hay sistema completo de cambio de tema.

### Decisión propuesta

Implementar:

- `light`;
- `dark`;
- `system`;
- persistencia en `localStorage`;
- toggle reutilizable para layout auth y panel admin.

### Criterio de aceptación

- El usuario puede cambiar tema desde UI.
- La preferencia sobrevive al refresh.
- `system` respeta `prefers-color-scheme`.
- El componente se podrá reutilizar en el dashboard.

---

## 7. Trabajo 5 — Layout base de panel admin

Antes de construir pantallas de Organizadores/Eventos, debe existir un layout base.

### Componentes esperados

```txt
resources/views/components/layout/
├── app-shell.blade.php
├── page-header.blade.php
└── content-card.blade.php

resources/views/components/navigation/
├── sidebar.blade.php
├── sidebar-link.blade.php
└── topbar.blade.php
```

### Decisiones pendientes

| Tema | Decisión pendiente |
|---|---|
| Sidebar | Fijo, colapsable o responsive-only. |
| Topbar | Necesaria para usuario, tema y acciones globales. |
| Breadcrumbs | Recomendados para panel admin. |
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
- [ ] componentes movidos a `form/` y `ui/`;
- [ ] decisión de dark/light mode;
- [ ] layout admin base definido o implementado.

---

## 11. Próximo paso inmediato

Ejecutar el mini-refactor:

```txt
components/auth/ → components/form/ + components/ui/
```

Después, crear el primer slice UX del panel admin.
