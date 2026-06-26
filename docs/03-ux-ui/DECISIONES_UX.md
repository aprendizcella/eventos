# Decisiones UX/UI

Registro de decisiones de experiencia de usuario, diseño visual y organización de componentes del boilerplate de eventos.

> **Decisión vigente:** construiremos un sistema UX propio sobre Laravel Blade/Volt + Tailwind. **TailAdmin será referencia visual**, no plantilla base para copiar. Los componentes reutilizables se organizarán por responsabilidad (`form/`, `ui/`, `layout/`, `navigation/`, etc.), no por la primera pantalla que los use.

---

## Estado

| Campo | Valor |
|---|---|
| Estado | Aceptado |
| Fecha | 26/06/2026 |
| Alcance | Auth UI, futuros paneles de administración/organización y sistema de componentes Blade |
| Base técnica | Laravel 12, Blade/Volt, Livewire, Tailwind CSS 4 |
| Referencia visual principal | TailAdmin Laravel |
| Referencia secundaria | Materio, solo para inspiración puntual |

---

## 1. Problema

El proyecto empieza a necesitar interfaz reutilizable: auth, panel de administración, organizadores, tablas, formularios, navegación y modo claro/oscuro.

Si cada pantalla copia clases Tailwind y estructura HTML a mano, aparecerán rápido estos problemas:

- formularios inconsistentes;
- botones con variantes distintas sin criterio;
- dark mode parcial;
- duplicación de SVG/scripts;
- dificultad para cambiar estilo global;
- pantallas de dominio acopladas al diseño;
- documentación dispersa sobre qué patrón usar.

La prioridad ahora es **crear fundamentos reutilizables antes de construir más pantallas de dominio**.

---

## 2. TailAdmin vs. Materio — qué se queda y qué no

| Aspecto | TailAdmin | Materio | Decisión |
|---|---|---|---|
| Estilo visual | Dashboard corporativo, limpio, muy "SaaS" | Material Design, más expresivo | **TailAdmin como referencia principal** de look & feel para paneles de organizador/admin. |
| Estructura de componentes | Árbol grande, muchos componentes específicos de dashboard | Más compacta | **No copiar el árbol completo** de TailAdmin. Tomar solo los patrones que aporten valor. |
| Stack visual | Tailwind CSS | Bootstrap 5 | Mantener Tailwind como stack principal; evitar mezclar Bootstrap con Tailwind. |
| Licencia / coste | Versión free limitada, versión pro de pago | Free con versión premium fuerte | Usar solo referencias libres o patrones reimplementables sin depender de assets propietarios. |

**Conclusión:** TailAdmin se usa como **referencia visual** (layout, tipografía, densidad de información, tratamiento de tablas y formularios), no como base que se copia carpeta por carpeta.

**Decisión explícita:** no se integrará TailAdmin ni Materio como dependencia o plantilla completa en este momento.

---

## 3. Sistema de componentes

Los componentes Blade del proyecto viven en `resources/views/components/`. La convención es:

- **Un componente = una responsabilidad visual.**
- **Prefieren composición** sobre herencia o slots complejos.
- **No duplican** lo que Tailwind ya resuelve bien; solo abstraen cuando hay un patrón repetido tres o más veces.
- **Se organizan por responsabilidad UI**, no por página:
  - `components/ui/` → primitivas genéricas (botón, link, badge, modal…).
  - `components/form/` → componentes de formulario reutilizables (field, password-input, select, checkbox…).
  - ~~`components/auth/`~~ → migrado a `form/` y `ui/` (ver [`COMPONENTES_UI.md`](./COMPONENTES_UI.md)).

### Estructura objetivo inicial

```txt
resources/views/components/
├── form/
│   ├── field.blade.php
│   └── password-input.blade.php
├── ui/
│   ├── button.blade.php
│   └── link.blade.php
├── layout/          # cuando exista layout admin real
├── navigation/      # cuando exista sidebar/topbar real
└── table/           # cuando existan listados reales
```

No se crearán carpetas vacías “por si acaso”. Las carpetas se crean cuando exista uso real.

---

## 4. Reglas no negociables

1. **No se introduce un componente nuevo sin un caso de uso real.** Nada de "por si acaso".
2. **Tailwind primero, componente después.** Si la clase de Tailwind resuelve el 90%, no se abstrae.
3. **Los componentes no conocen la página que los usa.** Se alimentan por props/slots.
4. **Accesibilidad básica obligatoria:** labels asociados, estados de foco, contraste AA.
5. **Tests de contrato visual mínimo:** si un componente protege un flujo crítico, la vista debe tener test de renderizado/contrato.
6. **Dark mode no puede ser parcial:** si se declara soporte dark, debe existir criterio para layout, inputs, botones y navegación.

---

## 5. Decisiones aceptadas

| ID | Decisión | Consecuencia |
|---|---|---|
| UX-001 | TailAdmin será referencia visual principal, no plantilla base. | Podemos inspirarnos en estructura, spacing y componentes sin acoplarnos a su repo. |
| UX-002 | Materio queda como referencia secundaria. | No se introduce Bootstrap ni dependencia de versión premium. |
| UX-003 | Componentes organizados por responsabilidad (`form/`, `ui/`, etc.). | Los componentes son reutilizables en auth, admin y dominio. |
| UX-004 | No iniciar nuevas pantallas de dominio sin base UX mínima. | Antes de Sprint 1.2 se estabiliza la foundation visual. |
| UX-005 | Dark/light mode será decisión explícita, no solo clases `dark:*`. | ✅ Implementado: `light`, `dark`, `system` con persistencia en `localStorage`. Toggle reutilizable en auth y admin layouts. |
| UX-006 | `AGENTS.md` será liviano; `docs/README.md` será el mapa documental. | La IA y humanos tienen entrada rápida sin inflar instrucciones. |
| UX-007 | Alpine.js para interactividad de UI (dropdowns, toggles, estado de componentes). | ✅ Implementado: theme toggle y mobile sidebar migrados de vanilla JS a Alpine.js. `resources/js/theme.js` eliminado. FOUC prevention sigue vía `theme-init.blade.php` inline. |

---

## 6. Consecuencias técnicas

### Positivas

- Menos duplicación de clases Tailwind.
- Más facilidad para cambiar estilo global.
- Mejor base para panel admin y pantallas de organizador.
- Componentes probables de reutilizar desde el primer sprint de dominio.
- Documentación más fácil de consultar por humanos y agentes.

### Costes

- Hay que invertir tiempo antes de Sprint 1.2.
- Algunos componentes actuales deberán moverse/renombrarse.
- TailAdmin no se puede “copiar y pegar”; habrá que reinterpretar patrones.

---

## 7. Próximas decisiones pendientes

| Tema | Pregunta pendiente |
|---|---|
| ~~Modo claro/oscuro~~ | ✅ Resuelto: `light`, `dark`, `system` con persistencia en `localStorage`. |
| ~~Layout admin~~ | ✅ Resuelto: sidebar fijo en desktop, oculto en mobile con toggle. Topbar con theme toggle. |
| ~~Interactividad JS~~ | ✅ Resuelto: Alpine.js para estado reactivo en theme toggle y mobile sidebar. Vanilla JS reemplazado. |
| Navegación | ¿Topbar con usuario/tema/notificaciones desde el inicio? (theme ya incluido, usuario/notificaciones pendientes) |
| Tablas | ¿Componente propio simple o patrón inspirado en TailAdmin? |
| Feedback UI | ¿Alertas/toasts/modales propios desde `ui/`? |
| Iconos | ¿SVG inline, Heroicons, Lucide u otra fuente? |

---

## 8. Próximo paso operativo

Seguir el orden descrito en [`PLAN_UX_FOUNDATION.md`](./PLAN_UX_FOUNDATION.md):

1. commit limpio del estado actual;
2. migrar componentes genéricos a `form/` y `ui/`;
3. decidir dark/light mode real;
4. crear layout base de panel admin;
5. comenzar Sprint 1.2 sobre esa base.

---

## Documentos relacionados

- [`COMPONENTES_UI.md`](./COMPONENTES_UI.md) — estructura actual y objetivo de componentes.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — observaciones del árbol de TailAdmin y qué aprovechar.
- [`PLAN_UX_FOUNDATION.md`](./PLAN_UX_FOUNDATION.md) — orden de trabajo antes de iniciar nuevas pantallas de dominio.
