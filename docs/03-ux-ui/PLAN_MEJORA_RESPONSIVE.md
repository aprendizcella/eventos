# Plan de Mejora Responsive y UX — TailAdmin

> **Estado de ejecución:** Implementado con verificación OpenSpec reportada; validación visual manual en navegador pendiente
> **Fecha de creación:** 28/06/2026  
> **Referencia visual:** TailAdmin Laravel  
> **Decisión actual:** tablas interactivas por dominio con Livewire Volt, no `<x-ui.table>` genérico.

---

## 1. Resultado Actual

La mejora responsive se resolvió con una estrategia distinta al plan inicial: en lugar de crear una tabla Blade genérica, se implementaron tablas Livewire Volt específicas por dominio.

El informe OpenSpec de `mini-sprint-responsive-ux` marca 9/9 tareas y 448/448 tests, pero el cierre manual en móvil, tablet y desktop sigue sin estar marcado. Por tanto, el estado es implementación verificada automáticamente con validación visual pendiente.

| Área | Estado | Implementación |
|---|---|---|
| Modal reutilizable | Implementado | `resources/views/components/ui/modal.blade.php` |
| Tabla Organizers | Implementado | `resources/views/livewire/organizers/organizers-table.blade.php` |
| Tabla Team | Implementado | `resources/views/livewire/organizers/team-table.blade.php` |
| Tabla Events | Implementado | `resources/views/livewire/organizers/events-table.blade.php` |
| Tabla Venues | Implementado | `resources/views/livewire/organizers/venues-table.blade.php` |
| Alpine.js | Implementado vía Livewire | `@livewireScripts` carga Alpine; `resources/js/app.js` no debe arrancarlo manualmente |
| `<x-ui.table>` | Descartado | La tabla genérica quedó fuera de la arquitectura actual |

---

## 2. Problema Resuelto

La aplicación tenía problemas de usabilidad en móvil y de consistencia visual en listados administrativos.

| Problema | Resolución |
|---|---|
| Modal de Team se salía del viewport | `x-ui.modal` usa overlay fijo, límite de altura y scroll interno. |
| Tablas se cortaban en móvil | Cada tabla Volt envuelve el `<table>` con `overflow-x-auto`. |
| Headers con múltiples acciones no se adaptaban | Toolbars usan `flex-col` en móvil y `md:flex-row` en pantallas mayores. |
| Filtros de Events ocupaban demasiado espacio | Events usa panel de filtros desplegable. |
| Tablas no seguían TailAdmin | Toolbars, search, columns dropdown, badges, hover, bordes y paginación se alinearon visualmente. |

---

## 3. Decisión Técnica Vigente

Usamos **Livewire Volt class-based SFC** para tablas administrativas con estado interactivo.

| Decisión | Motivo |
|---|---|
| Volt por dominio en vez de `<x-ui.table>` | Cada tabla tiene acciones, filtros y permisos distintos; forzar una abstracción genérica ahora escondería demasiada lógica. |
| Modal Blade reutilizable | El modal sí es una primitiva visual repetible y estable. |
| Alpine dentro de componentes | Se mantiene para dropdowns y visibilidad local, pero Livewire 4 lo carga automáticamente. |
| Acciones públicas autorizadas en servidor | Los métodos Livewire son invocables desde cliente; no basta con ocultar botones con `@can`. |
| Borrados scoped al organizer | Events y venues se resuelven desde `$this->organizer->events()` / `$this->organizer->venues()` antes de borrar. |

---

## 4. Componentes Implementados

### 4.1 Modal

`resources/views/components/ui/modal.blade.php`

Responsabilidades:

- Overlay accesible y centrado.
- Cierre por click exterior, Escape y botón de cierre.
- Scroll interno para contenido largo.
- Soporte dark mode.
- Uso desde componentes Livewire con expresiones como `open="$wire.showAddModal"`.

### 4.2 Tablas Volt

`resources/views/livewire/organizers/*.blade.php`

Responsabilidades compartidas:

- Búsqueda reactiva con `wire:model.live.debounce.300ms`.
- Ordenación por columna.
- Paginación y selector de tamaño de página.
- Dropdown de columnas visibles.
- Exportación CSV.
- Estados vacíos contextualizados.
- Acciones de fila con autorización en servidor.

Responsabilidades específicas:

| Componente | Responsabilidad especial |
|---|---|
| `organizers-table` | Listado global de organizers y borrado autorizado. |
| `team-table` | Alta, cambio de rol y baja de miembros con modales. |
| `events-table` | Filtros por estado, visibilidad y rango de fechas. |
| `venues-table` | Listado y borrado scoped de venues. |

---

## 5. Criterios de Aceptación

| Criterio | Estado |
|---|---|
| Modal no se sale del viewport móvil | Implementado |
| Modal tiene scroll interno | Implementado |
| Tablas tienen scroll horizontal intencional en móvil | Implementado |
| Tablas soportan búsqueda | Implementado |
| Tablas soportan ordenación | Implementado |
| Tablas soportan paginación | Implementado |
| Events soporta filtros desplegables | Implementado |
| Acciones Livewire de escritura autorizan en servidor | Implementado |
| QA completo actual | Aprobado según el informe OpenSpec |
| Build frontend | Aprobado según el informe OpenSpec |
| Tests específicos Livewire/Volt | Implementados en `tests/Feature/LivewireOrganizerTablesTest.php` |
| Validación visual manual | Pendiente |

---

## 6. Verificación Pendiente

Antes de cerrar el mini-sprint queda pendiente:

1. Validar manualmente las rutas principales en móvil, tablet y desktop; esta comprobación continúa pendiente.
2. Ejecutar Sonar con `./sonar.sh` si se quiere cerrar la iteración con gate externo actualizado.

---

## 7. Referencias Visuales TailAdmin

Tomamos estos patrones, sin importar TailAdmin como dependencia:

- Toolbar superior con búsqueda, acciones y botón de descarga.
- Panel de filtros colapsable.
- Bordes suaves, hover en filas y fondo de cabecera tenue.
- Badges de estado.
- Iconos compactos para acciones de fila.
- Paginación inferior con resumen de resultados.

---

## 8. Documentos Relacionados

- [`DECISIONES_UX.md`](./DECISIONES_UX.md) — filosofía UX y decisiones vigentes.
- [`COMPONENTES_UI.md`](./COMPONENTES_UI.md) — estructura real de componentes.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — qué tomar de TailAdmin.
- [`PLAN_UX_FOUNDATION.md`](./PLAN_UX_FOUNDATION.md) — foundation UX previa.
