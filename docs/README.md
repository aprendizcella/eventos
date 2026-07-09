# Documentación del proyecto — Eventos

Índice de navegación de la documentación del boilerplate Laravel para la plataforma de eventos y ticketing.

> **Si solo vas a leer un archivo**, empieza por [`00-estado/ESTADO_EJECUCION.md`](./00-estado/ESTADO_EJECUCION.md): resume dónde estamos hoy y qué queda por hacer.

---

## Navegación rápida

| Quiero saber… | Lee este documento |
|---|---|
| En qué punto está el proyecto (Sprint 4.2, próximos pasos) | [`00-estado/ESTADO_EJECUCION.md`](./00-estado/ESTADO_EJECUCION.md) |
| Qué se va a construir, fases y sprints | [`01-producto/PLAN_IMPLEMENTACION.md`](./01-producto/PLAN_IMPLEMENTACION.md) |
| Cómo está montado el backend (capas, flujos, convenciones) | [`02-arquitectura/ESPECIFICACION_TECNICA_BOILERPLATE_EVENTOS.md`](./02-arquitectura/ESPECIFICACION_TECNICA_BOILERPLATE_EVENTOS.md) |
| Cómo encaja la propuesta DDD en el boilerplate | [`02-arquitectura/MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./02-arquitectura/MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) |
| Qué decisiones de arquitectura se han tomado y por qué | [`02-arquitectura/DECISIONES_ARQUITECTURA.md`](./02-arquitectura/DECISIONES_ARQUITECTURA.md) |
| Criterios UX/UI, referencia TailAdmin y filosofía de componentes | [`03-ux-ui/DECISIONES_UX.md`](./03-ux-ui/DECISIONES_UX.md) |
| Qué hacer antes de iniciar nuevas pantallas de dominio | [`03-ux-ui/PLAN_UX_FOUNDATION.md`](./03-ux-ui/PLAN_UX_FOUNDATION.md) |
| Estructura actual de componentes y objetivo (`form/` + `ui/`) | [`03-ux-ui/COMPONENTES_UI.md`](./03-ux-ui/COMPONENTES_UI.md) |
| Observaciones del árbol de componentes de TailAdmin y por qué no copiarlo entero | [`03-ux-ui/REFERENCIAS_UX.md`](./03-ux-ui/REFERENCIAS_UX.md) |
| Plan de mejora responsive y UX para alinear con TailAdmin | [`03-ux-ui/PLAN_MEJORA_RESPONSIVE.md`](./03-ux-ui/PLAN_MEJORA_RESPONSIVE.md) |
| Librerías evaluadas, compatibilidad y estrategia de integración | [`04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md`](./04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md) |
| Cómo configurar y probar Stripe localmente en modo pruebas | [`05-guias/stripe_local_setup.md`](./05-guias/stripe_local_setup.md) |
| Análisis de deuda técnica post Sprint 3.2 (qué refactorizar y cuándo) | [`06-deuda-tecnica/ANALISIS_ARQUITECTONICO_FASE3.md`](./06-deuda-tecnica/ANALISIS_ARQUITECTONICO_FASE3.md) |

---

## Mapa de carpetas

```text
docs/
├── README.md                  ← estás aquí
├── 00-estado/                 ← estado actual del proyecto
│   └── ESTADO_EJECUCION.md
├── 01-producto/               ← visión, roadmap y plan de sprints
│   └── PLAN_IMPLEMENTACION.md
├── 02-arquitectura/           ← decisiones técnicas y estructurales
│   ├── ESPECIFICACION_TECNICA_BOILERPLATE_EVENTOS.md
│   ├── MAPING_PROPUESTA_DDD_A_BOILERPLATE.md
│   └── DECISIONES_ARQUITECTURA.md
├── 03-ux-ui/                  ← decisiones de UI/UX y componentes
│   ├── DECISIONES_UX.md
│   ├── PLAN_UX_FOUNDATION.md
│   ├── COMPONENTES_UI.md
│   ├── REFERENCIAS_UX.md
│   └── PLAN_MEJORA_RESPONSIVE.md
├── 04-librerias/              ← valoración de dependencias
│   └── VALORACION_LIBRERIAS_INTEGRACION.md
├── 05-guias/                  ← guías de desarrollo y procedimientos
│   └── stripe_local_setup.md
└── 06-deuda-tecnica/          ← análisis de deuda técnica y refactoring pendiente
    └── ANALISIS_ARQUITECTONICO_FASE3.md
```

---

## Convenciones de esta documentación

- **Idioma:** español neutro/profesional (coherente con los docs existentes).
- **Estado de ejecución:** cada documento relevante lleva una nota `> Estado de ejecución (post Sprint X.X)` al principio para situar al lector.
- **Enlaces internos:** se usan rutas relativas para que el árbol sea navegable desde GitHub, GitLab o el editor.
- **Código y rutas:** en inglés, tal como aparecen en el repositorio.

## Siguiente paso recomendado

1. Leer [`00-estado/ESTADO_EJECUCION.md`](./00-estado/ESTADO_EJECUCION.md) para situarte.
2. Elegir el siguiente sprint en [`01-producto/PLAN_IMPLEMENTACION.md`](./01-producto/PLAN_IMPLEMENTACION.md).
3. Revisar arquitectura y UX antes de implementar.
