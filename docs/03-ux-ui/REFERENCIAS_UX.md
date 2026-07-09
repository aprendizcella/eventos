# Referencias UX — TailAdmin

Observaciones sobre el árbol de componentes de TailAdmin y criterios para decidir **qué aprovechar y qué no copiar**.

> **En una línea:** TailAdmin es una **fuente de patrones**, no una base que se importa carpeta por carpeta. Copiar todo el árbol traería coste de mantenimiento sin beneficio proporcional.

---

## 1. Árbol típico de TailAdmin (observación)

TailAdmin (versión free/pro) organiza sus componentes en bloques del tipo:

```text
TailAdmin/
├── components/
│   ├── header/
│   ├── sidebar/
│   ├── breadcrumbs/
│   ├── tables/
│   ├── forms/
│   │   ├── input-group/
│   │   ├── checkbox/
│   │   ├── radio/
│   │   ├── select/
│   │   ├── switch/
│   │   └── ...
│   ├── cards/
│   ├── modals/
│   ├── dropdowns/
│   ├── alerts/
│   ├── buttons/
│   └── dashboards/
└── layouts/
```

Es un árbol **extenso y específico de dashboard**: cada tipo de input, cada variante de tarjeta, cada variante de tabla tiene su propia carpeta y sus propios ejemplos.

---

## 2. Qué NO copiar y por qué

| Bloque de TailAdmin | Por qué no copiarlo tal cual |
|---|---|
| `dashboards/` | Son páginas completas, no componentes. No encajan en nuestro modelo de páginas Livewire/Volt. |
| `forms/` con una carpeta por input | Over-engineering para el estado actual del proyecto. Un `field.blade.php` genérico cubre el 80% de los casos. |
| `tables/` muy especializadas | Nuestras tablas de dominio (eventos, órdenes, asistentes) aún no existen. Abstraer ahora sería especulativo. |
| `header/`, `sidebar/`, `breadcrumbs/` completos | Los layouts de dashboard se construirán cuando haya un rol organizador/admin con panel. No antes. |
| Assets / iconos propietarios | Dependencia de licencia. Mejor usar Heroicons / Lucide (libres). |

---

## 3. Qué SÍ aprovechar

| Patrón de TailAdmin | Cómo lo adaptamos |
|---|---|
| Densidad de información en tablas | Usar como referencia visual cuando construyamos las tablas de dominio. |
| Formularios en dos columnas con labels arriba | Patrón por defecto para formularios de creación/edición de entidades. |
| Cards de métricas con título, valor y delta | Reutilizable en el panel de organizador (Fase 3+). |
| Estados vacío / loading / error en listas | Patrón a seguir para `index.blade.php` de cada dominio. |
| Tratamiento de errores de validación inline | Ya lo tenemos en `auth/field.blade.php`; extender al resto de formularios. |

---

## 4. Regla práctica

> **Antes de importar un componente de TailAdmin:** preguntarse si existe ya un equivalente en Tailwind + un componente propio. Si la respuesta es sí, no se importa. Si la respuesta es no, se implementa primero como caso concreto y solo se abstrae tras el tercer uso.

---

## 5. Referencia adicional para monetización

Para Sprint 4.2 se toma como referencia funcional la UX de HI.EVENTS en las superficies de tarifas, informes y configuración de pagos.

| Superficie | Patrón útil |
|---|---|
| Settings de tarifas | Tabs claras, card de estado, simulación de comisión y CTA visible para conexión de pagos. |
| Informes financieros | Filtros arriba, tabla abajo, totales resumidos y export CSV. |
| Avisos contextuales | Banner corto con aviso operativo/legal para no confundir la vista interna con contabilidad externa. |

La referencia sirve para estructura y jerarquía visual, no para replicar el diseño exacto.

---

## Documentos relacionados

- [`DECISIONES_UX.md`](./DECISIONES_UX.md) — decisión TailAdmin/Materio y filosofía de componentes.
- [`COMPONENTES_UI.md`](./COMPONENTES_UI.md) — estructura actual y objetivo.
