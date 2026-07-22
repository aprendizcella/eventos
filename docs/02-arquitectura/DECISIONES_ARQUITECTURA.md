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
| A11 | Modelo de tenancy = una sola BBDD con `organizer_id` como scope; dominios propios solo como branding/routing | El proyecto se mantiene tenant-aware sin separación física por tenant. El dominio `Organizer.domain` existe para marca y futura resolución por host, pero no implica DB por tenant. | [`VALORACION_LIBRERIAS_INTEGRACION.md`](../04-librerias/VALORACION_LIBRERIAS_INTEGRACION.md) |
| A12 | Onboarding de usuarios = invitación/alta asistida en sprint posterior | La creación directa de usuarios asociados a organizer se pospone para decidir el mejor flujo de producto sin mezclarlo con el CRUD de organizers y roles del equipo. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 1.5 |
| A13 | Organizer roles = catálogo propio del dominio | Los roles `admin`, `editor`, `viewer` del organizer no se modelan con Spatie Permission porque son pivot/domain roles, no roles globales. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 1.2 + implementación en `app/Support/Organizers/OrganizerRoles.php` |
| A14 | Aplazamiento de la integración de Stripe Elements (Frontend) a pre-producción/Staging | El backend de Stripe (firma HMAC, webhooks, reembolsos, stock) está 100% operativo. El formulario real en la UI se pospone a Staging; en local se usan simuladores (offline y webhook). | [`docs/05-guias/stripe_local_setup.md`](../05-guias/stripe_local_setup.md) |
| A15 | Gestión de Check-in desacoplada y Auditoría inmutable | Se implementó una tabla de paso `active_check_in` para controlar el estado actual de los accesos y una tabla `check_in_log` para registrar de forma inmutable el historial completo de acciones para auditoría. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 3.1 |
| A16 | Control de Concurrencia y Locks en Check-in | Las acciones `CheckInAttendeeAction` y `UndoCheckInAction` ejecutan transacciones de base de datos de forma atómica y aplican bloqueos de registro (`lockForUpdate()`) en el mismo orden de tablas para evitar deadlocks y condiciones de carrera. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 3.1 |
| A17 | Prevención de Doble Escaneo y Control de Aspect-Ratio en UI | Lector QR basado en `html5-qrcode` integrado en un contenedor auto-ajustable (evitando distorsión de video) con detención automática del stream al detectar el código para evitar lecturas duplicadas continuas. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 3.1 |
| A18 | Precedencia de tenant = host primero, fallback de ruta solo en panel interno, global sin tenant | El tenant resuelto por el host del dominio raíz configurado por `APP_URL` es la fuente de verdad para accesos públicos. `organizer.detect` puede servir como fallback en rutas internas. La sesión nunca decide el tenant. | `openspec/changes/sprint-t0-multitenancy-foundation/design.md` |
| A19 | Superadmin global = panel sin organizer activo | El superadministrador debe poder entrar en contexto global desde el dominio raíz configurado por `APP_URL`, ver todos los organizers y cambiar de tenant sin quedar atado a un organizer concreto. | `openspec/changes/sprint-t0-multitenancy-foundation/design.md` |
| A20 | Numeración de factura = organizador + año | La serie de facturación debe ser estable por `organizer_id` y año natural para evitar colisiones entre organizers y facilitar auditoría. | `openspec/changes/sprint-4-1-facturacion/design.md` |
| A21 | Base de facturación 4.1a = precisión exacta antes de automatizar facturas | La primera entrega de Sprint 4.1 debe resolver los importes exactos y el almacenamiento mínimo de invoice/settings antes de listeners, PDF o UX. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 4.1a |
| A22 | Sprint 4.2 = tracking interno de comisiones y payouts | El bloque de monetizacion registra comisiones y payouts sin mover dinero real; Stripe Connect queda diferido para una fase posterior. | `docs/01-producto/PLAN_IMPLEMENTACION.md` §Sprint 4.2 |
| A23 | Auditoría global = lectura segura y clasificación persistida | Sprint 6.2a usa una frontera `ViewModel/DTO` de solo lectura con proyección segura, excluye payloads sensibles, ordena de forma determinista, pagina con límite y falla cerrando sin filtrar detalles. Solo `super_admin` accede a esta regla sensible; la observabilidad es redacted. | [`04-admin-platform.md`](./04-admin-platform.md) §Global Audit Visibility; SDD `sprint-6-2a-audit-visibility` |
| A24 | UX de auditoría global = integrada, responsive y basada en patrones existentes | Dirección aprobada para Sprint 6.2: auditoría dentro de reporting/control de plataforma, sin copiar la identidad o navegación de HI.EVENTS ni crear una superficie paralela. La presentación depende de reconciliar antes el contrato `organizer_id IS NULL AND is_global = true`. | [`04-admin-platform.md`](./04-admin-platform.md) §Dirección UX aprobada para Sprint 6.2 |

> **Nota de cierre:** Sprint 6.2a verificó y archivó el contrato `organizer_id IS NULL AND is_global = true` el 2026-07-22.

---

## Decisiones pendientes de formalizar

- **IDs:** ¿autoincremental (`{model}_id`) o UUID v7? Hoy vale la convención del boilerplate (autoincremental). Si se migra a UUID, debe quedar registrado aquí.
- **Repositories:** opcionales según complejidad de la Action. Falta criterio escrito de "cuándo sí, cuándo no".
- **Estructura UI:** ~~movimiento de `components/auth/` a `components/form/` + `components/ui/`~~ → **hecho**. Ver [`03-ux-ui/COMPONENTES_UI.md`](../03-ux-ui/COMPONENTES_UI.md).
- **Tenancy física:** el proyecto permanece en **single DB + `organizer_id` como scope**. Si en el futuro se decide multi-DB, debe abrirse una nueva decisión explícita.
- **Dominio raíz:** el acceso global de superadmin se valida en el host configurado por `APP_URL` para cada entorno; `localhost` solo describe el entorno local actual, no una regla funcional.
- **Facturación 4.1a:** la numeración de factura queda fijada por organizador y año; si cambia el formato de serie, debe abrirse una decisión nueva.
- **Sprint 4.2:** comisiones y payouts quedaron implementados como tracking interno; si se decide mover dinero real, habrá que abrir una decisión separada sobre Stripe Connect y operación financiera.

---

## Cómo añadir una nueva decisión

1. Añadir una fila a la tabla con un identificador secuencial (`A12`, `A13`…).
2. Contexto en una línea.
3. Enlace al documento donde se justifica (spec, design, o una nota en este mismo archivo si es menor).
4. Si la decisión reemplaza a una anterior, dejar la fila antigua marcándola como **superseded** y apuntar a la nueva.

### A23 — Detalle de la frontera de lectura de auditoría

- El ViewModel consulta únicamente filas con `organizer_id IS NULL AND is_global = true`; no infiere clasificación desde el tenant de la request.
- La frontera ViewModel/DTO proyecta solo identificadores, metadatos del evento, descripción, identidades y timestamp; no transporta `properties`, `attribute_changes` ni payloads sin redacción.
- La navegación es read-only, latest-first, con `created_at DESC, id DESC` y paginación positiva acotada.
- Denegaciones, exclusiones y errores producen observabilidad estructurada redacted; los errores de consulta no muestran filas parciales ni detalles de excepción.

### A24 — Dirección UX de auditoría global

- Se adapta la claridad operativa de HI.EVENTS, sin reutilizar su marca, tipografía, iconografía, taxonomía de navegación ni layouts solo de escritorio.
- Se reutilizan los patrones de reportes, dashboard y tablas de Eventos; no se crea un framework genérico de tablas ni gráficos sin datos significativos.
- La vista prevista incluye cabecera contextual, estado explícito de inmutabilidad y solo lectura, contador de resultados, filtros en servidor con chips y reinicio, filas semánticas escaneables, presentación móvil apilada y estados seguros de carga, vacío y error.
- Esta decisión conserva el acceso exacto de `super_admin` y la proyección segura. Antes de mostrar agregados o filtros, se debe reconciliar la clasificación global con `organizer_id IS NULL AND is_global = true`.
