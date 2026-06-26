# Decisiones UX

Decisiones de producto y diseño visual aplicadas al boilerplate de eventos.

> **En una línea:** usamos **TailAdmin** como referencia visual (no como plantilla para copiar), **Matero/Materio** como segunda referencia de patrones, y construimos nuestros propios componentes reutilizables empezando por los de auth.

---

## 1. TailAdmin vs. Materio — qué se queda y qué no

| Aspecto | TailAdmin | Materio | Decisión |
|---|---|---|---|
| Estilo visual | Dashboard corporativo, limpio, muy "SaaS" | Material Design, más expresivo | **TailAdmin como referencia principal** de look & feel para paneles de organizador/admin. |
| Estructura de componentes | Árbol grande, muchos componentes específicos de dashboard | Más compacta | **No copiar el árbol completo** de TailAdmin. Tomar solo los patrones que aporten valor. |
| Licencia / coste | Versión free limitada, versión pro de pago | Open source (Material) | Usar solo lo que esté libre o se pueda reimplementar sin depender de assets propietarios. |

**Conclusión:** TailAdmin se usa como **referencia visual** (layout, tipografía, densidad de información, tratamiento de tablas y formularios), no como base que se copia carpeta por carpeta.

---

## 2. Filosofía de la carpeta de componentes

Los componentes Blade del proyecto viven en `resources/views/components/`. La convención es:

- **Un componente = una responsabilidad visual.**
- **Prefieren composición** sobre herencia o slots complejos.
- **No duplican** lo que Tailwind ya resuelve bien; solo abstraen cuando hay un patrón repetido tres o más veces.
- **Se organizan por dominio funcional**, no por página:
  - `components/ui/` → primitivas genéricas (botón, input, label, badge, modal…).
  - `components/form/` → componentes de formulario reutilizables (field, password-input, select, checkbox…).
  - `components/auth/` → **hoy**: componentes específicos de auth. **Objetivo:** migrar a `form/` y `ui/` porque no tienen lógica específica de auth (ver [`COMPONENTES_UI.md`](./COMPONENTES_UI.md)).

---

## 3. Reglas no negociables

1. **No se introduce un componente nuevo sin un caso de uso real.** Nada de "por si acaso".
2. **Tailwind primero, componente después.** Si la clase de Tailwind resuelve el 90%, no se abstrae.
3. **Los componentes no conocen la página que los usa.** Se alimentan por props/slots.
4. **Accesibilidad básica obligatoria:** labels asociados, estados de foco, contraste AA.

---

## Documentos relacionados

- [`COMPONENTES_UI.md`](./COMPONENTES_UI.md) — estructura actual y objetivo de componentes.
- [`REFERENCIAS_UX.md`](./REFERENCIAS_UX.md) — observaciones del árbol de TailAdmin y qué aprovechar.
- [`PLAN_UX_FOUNDATION.md`](./PLAN_UX_FOUNDATION.md) — orden de trabajo antes de iniciar nuevas pantallas de dominio.
