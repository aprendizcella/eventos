# Componentes UI

Estado actual de la carpeta de componentes Blade y objetivo de reorganización.

> **En una línea:** hoy los componentes reutilizables viven bajo `components/auth/` pero **no son específicos de auth**; el objetivo es moverlos a `components/form/` (primitivas de formulario) y `components/ui/` (primitivas visuales genéricas).

---

## 1. Estructura actual

```text
resources/views/
├── components/
│   └── auth/
│       ├── button.blade.php
│       ├── field.blade.php
│       ├── link.blade.php
│       └── password-input.blade.php
├── livewire/
│   └── auth/
│       ├── forgot-password.blade.php
│       ├── login.blade.php
│       ├── register.blade.php
│       └── reset-password.blade.php
└── layouts/
```

### Qué hace cada componente hoy

| Componente | Responsabilidad real | ¿Es específico de auth? |
|---|---|---|
| `auth/button.blade.php` | Botón con estilo primario para formularios. | **No.** Es un botón de formulario genérico. |
| `auth/field.blade.php` | Label + input + mensajes de error. | **No.** Aplica a cualquier campo de formulario. |
| `auth/link.blade.php` | Enlace con estilo de texto secundario. | **No.** Enlace genérico de UI. |
| `auth/password-input.blade.php` | Input de contraseña con toggle de visibilidad. | **Casi.** Se usa en auth hoy, pero es reutilizable en cualquier formulario que pida password (profile, cambio de contraseña, etc.). |

---

## 2. Estructura objetivo

```text
resources/views/components/
├── form/
│   ├── button.blade.php        ← ex auth/button
│   ├── field.blade.php         ← ex auth/field
│   └── password-input.blade.php ← ex auth/password-input
└── ui/
    └── link.blade.php          ← ex auth/link
```

### Criterio de clasificación

- **`form/`** → todo lo que forma parte de un `<form>`: inputs, botones de submit, validación inline, password toggle.
- **`ui/`** → primitivas visuales que no pertenecen a un formulario: links, badges, iconos, modales, tooltips.

---

## 3. Plan de migración (borrador)

1. Crear carpetas `components/form/` y `components/ui/`.
2. Mover archivos con `git mv` para conservar historial.
3. Actualizar referencias en las vistas Livewire de auth (`<x-auth-button>` → `<x-form-button>`, etc.).
4. Buscar otros usos en el proyecto (`rg '<x-auth-'`) y actualizar.
5. Eliminar carpeta `components/auth/` si queda vacía.
6. Verificar con `composer test` y revisión visual.

> **Nota:** esta migración es pequeña y puede empaquetarse como un commit `refactor:` al inicio del Sprint 1.2, antes de añadir nuevos componentes de dominio.

---

## 4. Convenciones de autoría

- Props tipadas cuando sea posible (`@props(['variant' => 'primary'])`).
- Slots con nombre solo cuando haya más de uno.
- Clases de Tailwind compuestas con `@class([...])` o `merge`.
- Documentar el componente con un comentario breve al inicio del archivo si tiene variantes o estados no obvios.

---

## Documentos relacionados

- [`DECISIONES_UX.md`](./DECISIONES_UX.md) — filosofía general y elección TailAdmin/Materio.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — qué tomar de TailAdmin y qué descartar.
