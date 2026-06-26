# Decisiones de arquitectura

Índice de decisiones arquitectónicas tomadas hasta la fecha. Cada entrada apunta al documento donde se justifica y detalla.

> **Objetivo:** que alguien que llegue al proyecto pueda entender **qué se decidió, por qué, y dónde leer más**, sin tener que reconstruir la historia.

---

## Tabla de decisiones

| # | Decisión | Contexto breve | Documento de referencia |
|---|---|---|---|
| A1 | Mantener la estructura plana del boilerplate (por tipo) en vez de carpetas por Bounded Context | La propuesta DDD original proponía `app/EventManagement/Domain/`, etc. Se descarta para respetar las convenciones del boilerplate y mantener la curva de aprendizaje baja. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A2 | Flujo de escritura = `FormRequest → DTO → Controller → Action` | Reemplaza al `Command → Handler → Repository` de la propuesta DDD. `Action` hace de Handler. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A3 | Flujo de lectura = `Controller → ViewModel / Resource` | Reemplaza al `Query → Handler → ReadModel` DDD. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A4 | DB = MariaDB 11 (no PostgreSQL) | Impuesto por el boilerplate. Se usa JSON en vez de JSONB y funciones MySQL. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A5 | Auth = Sanctum (no Passport, no JWT puro) | Sanctum cubre SPA (cookie) y API (token) sin la complejidad de Passport. | [`VALORACION_LIBRERIAS_INTEGRACION.md`](../04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md) §2.1 |
| A6 | HTML seguro = `mews/purifier` como wrapper de HTMLPurifier | HTMLPurifier es la herramienta correcta; el wrapper mejora la integración Laravel. | [`VALORACION_LIBRERIAS_INTEGRACION.md`](../04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md) §2.2 |
| A7 | Frontend MVP = Blade + Tailwind CSS 4 + Livewire/Volt | Se pospone React/TypeScript a una fase posterior si el producto lo requiere. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A8 | Testing = Pest 4.x (no Playwright por ahora) | Playwright solo se introducirá si hay frontend React. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A9 | QA pipeline = Rector → Pint → PHPStan → Pest → SonarQube | Obligatorio antes de cada commit. | `AGENTS.md` · sección QA |
| A10 | Domain Events = sistema nativo de Laravel Events | No se introduce un dispatcher propio. | [`MAPING_PROPUESTA_DDD_A_BOILERPLATE.md`](./MAPING_PROPUESTA_DDD_A_BOILERPLATE.md) §1 |
| A11 | Multi-tenencia = `spatie/laravel-multitenancy`, aplazado a Fase 4 | Compatible con L12, pero fuera del alcance de la fundación. | [`VALORACION_LIBRERIAS_INTEGRACION.md`](../04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md) |

---

## Decisiones pendientes de formalizar

- **IDs:** ¿autoincremental (`{model}_id`) o UUID v7? Hoy vale la convención del boilerplate (autoincremental). Si se migra a UUID, debe quedar registrado aquí.
- **Repositories:** opcionales según complejidad de la Action. Falta criterio escrito de "cuándo sí, cuándo no".
- **Estructura UI:** movimiento de `components/auth/` a `components/form/` + `components/ui/`. Ver [`03-ux-ui/COMPONENTES_UI.md`](../03-ux-ui/COMPONENTES_UI.md).

---

## Cómo añadir una nueva decisión

1. Añadir una fila a la tabla con un identificador secuencial (`A12`, `A13`…).
2. Contexto en una línea.
3. Enlace al documento donde se justifica (spec, design, o una nota en este mismo archivo si es menor).
4. Si la decisión reemplaza a una anterior, dejar la fila antigua marcándola como **superseded** y apuntar a la nueva.
