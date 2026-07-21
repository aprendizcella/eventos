# Estado de ejecución

> **Resumen en una línea:** El repositorio está en el estado posterior al commit `d2e9cbe` (2026-07-20): Sprints 1.1–5.4 y Sprint 6.1 están implementados; Sprint 6.1 dispone de informe OpenSpec PASS, pero su archivo no contiene `archive-report.md`, por lo que esa evidencia no equivale a una nueva verificación independiente. La Fase 6.2–6.4 y varios cambios OpenSpec activos siguen pendientes o con evidencia incompleta. En Sprint 6.2a existe una deuda conocida: el filtro global debe seguir siendo `organizer_id IS NULL AND is_global = true`, pero la documentación/evidencia aún no está completamente reconciliada.

---

## Qué está hecho

### Sprint 1.1 — Setup y Auth ✅

- **Stack base instalado y operativo:** Laravel 12 / PHP 8.4 / Sail, Sanctum, Spatie Permission, Spatie Activitylog, Mews/Purifier, Livewire + Volt.
- **Auth flows funcionales:** registro, login, logout, reset de password, verificación de email (`MustVerifyEmail`).
- **Roles y permisos:** Spatie Permission con `RoleSeeder` (seis roles base).
- **Trazabilidad:** Spatie Activitylog activo sobre `User` y entidades relevantes.
- **Migraciones y seeders:** `permission_tables`, `activity_log`, `personal_access_tokens` desplegados.
- **Tests en verde** y pipeline QA (`composer qa`: rector → pint → phpstan → tests) limpio.
- **Checks confirmados post-merge:** login/logout/reset, email verification gate, account profile/password, team management y organizer CRUD.

### Refactor de componentes Auth (UI)

- Componentes Blade genéricos reorganizados bajo `resources/views/components/`:
  - `form/field.blade.php`
  - `form/password-input.blade.php`
  - `ui/button.blade.php`
  - `ui/link.blade.php`
- La antigua carpeta `components/auth/` se eliminó tras la migración.
- Páginas Livewire/Volt de auth bajo `resources/views/livewire/auth/`:
  - `login.blade.php`
  - `register.blade.php`
  - `forgot-password.blade.php`
  - `reset-password.blade.php`
- Las vistas de auth usan `<x-form.*>` y `<x-ui.*>` (ver [`03-ux-ui/COMPONENTES_UI.md`](../03-ux-ui/COMPONENTES_UI.md)).

### UX Foundation — Dark/Light Mode y Layout Admin ✅

- **Dark/light mode implementado:**
  - Soporte para `light`, `dark`, `system` con persistencia en `localStorage`.
  - Toggle reutilizable (`<x-ui.theme-toggle />`) en auth layout y admin layout, manejado por Alpine.js.
  - Script inline (`<x-ui.theme-init />`) para prevenir FOUC (corre antes de Alpine).
  - Clase `dark` aplicada en `documentElement` según tema activo.
  - `resources/css/app.css` configurado con `@custom-variant dark (&:where(.dark, .dark *))`.
  - `resources/js/theme.js` eliminado; Alpine.js maneja la lógica de tema.

- **Layout admin base implementado:**
  - `layouts/app.blade.php` — layout principal del panel admin.
  - `layout/app-shell.blade.php` — estructura: sidebar + topbar + main. Estado de sidebar en Alpine.js.
  - `navigation/sidebar.blade.php` — sidebar con navegación (Dashboard, Events, Organizers). Toggle reactivo con Alpine.
  - `navigation/topbar.blade.php` — topbar con theme toggle y menú de usuario.
  - Responsive: sidebar oculto en mobile, visible en lg+ con toggle. Estado manejado por Alpine.js (`x-data="{ sidebarOpen: false }"`).

- **Alpine.js integrado:**
  - Instalado vía `npm install alpinejs` (en `dependencies`).
  - Inicializado en `resources/js/app.js`.
  - Theme toggle y mobile sidebar migrados de vanilla JS a Alpine.js.
  - Vite build operativo (92KB JS bundle incluye Alpine).

- **Dashboard placeholder:**
  - `resources/views/livewire/dashboard.blade.php` — página Volt mínima.
  - Ruta `/dashboard` protegida con middleware `auth`.
  - Tests de contrato en `tests/Feature/AdminLayoutTest.php`.

### Sprint 1.2 — Organizadores y Equipos ✅

- CRUD de organizers implementado y verificado.
- Team management implementado and verificado.
- Roles del organizer modelados como catálogo propio (`admin`, `editor`, `viewer`) separado de Spatie.
- Policies y test coverage para global admins y organizer admins en verde.

### Sprint 1.3 — Eventos Básicos ✅

- Taxonomía global de categorías implementada con `CategorySeeder` idempotente.
- Venues y events modelados con tablas singulares, PK `{model}_id`, SoftDeletes, factories y relaciones con organizer.
- CRUD interno de eventos implementado con DTOs, FormRequests, Actions y controller fino.
- Ciclo de vida cubierto: draft/configured/published/paused/completed/cancelled, con publish/pause/cancel actions.
- Descripciones HTML se sanitizan con Purifier antes de persistir.
- Policies de events/venues cubren admin/editor/viewer, global admins y acceso cruzado entre organizers.
- UI interna Blade para listado, filtros, creación, edición, detalle y acciones publish/pause/cancel.
- `composer qa` pasa limpio: 415 tests, 1059 assertions.

### Account UX ✅

- Menú de usuario en topbar con nombre, rol, organizer, Profile y Sign out.
- Perfil editable para nombre; email visible pero read-only.
- Cambio de contraseña accesible desde profile y topbar.
- Remember me opt-in en login.

### Email Verification Gate ✅

- Usuarios no verificados bloqueados de dashboard, account y organizers.
- Notice / resend / callback / logout accesibles.
- Registro redirige a verificación.
- Seed/admin users pueden quedar pre-verificados.

### Sprint 1.4 — Panel de Organizador ✅

- **Estructura de Navegación Contextual:** Sidebar y switcher de tenant actualizados con ordenación determinista.
- **Dashboard Global del Organizador:** Métricas de negocio unificadas (Ventas y Registros como placeholders de diseño, Eventos Activos y Miembros del equipo reales), acompañado de un feed visual de **Pedidos Recientes**.
- **Panel de Ajustes Multitab (Alpine.js):** Pestañas independientes para Información Básica, Dirección, Redes Sociales, Valores Predeterminados y Zona de Peligro, con reautorización robusta en la mutación Livewire.
- **Sub-navegación del Evento:** Detalle de evento rediseñado con navegación interna por pestañas (Vista general, Entradas, Asistentes y Acciones de Ciclo de Vida) para consolidar los KPIs de forma limpia.
- **API REST básica:** Endpoints anidados `/api/v1/organizers/{organizer}` y `/api/v1/organizers/{organizer}/events` asegurados por Sanctum con contrato explícito (settings filtrado sin datos privados) y aislados por el middleware `organizer.detect`.
- **Paso limpio del pipeline de QA completo:** Suite de tests ejecutada en Pest de manera íntegra y sin filtros.

### Sprint 2.1 — Productos y Tipos de Entrada ✅

- **Modelos y Estructura:** Modelado de `Product`, `ProductPrice` y `PromoCode` con claves e índices adecuados y SoftDeletes.
- **Acciones y Lógica:** Acciones `CreateProductAction` y `UpdateProductAction` para encapsular la lógica de negocio; `PriceCalculator` y `PromoCodeValidator` para el cálculo dinámico y validación de precios y cupones.
- **Seguridad:** Encriptación/hash de contraseña para tickets protegidos por contraseña.
- **UI de Tickets:** Componentes Blade reutilizables bajo la filosofía de la UX Foundation.

### Sprint 2.2 — Órdenes y Checkout ✅

- **Estructura Transaccional:** Tablas `ticket_order` y `ticket_order_item`. Enum `TicketOrderStatus`.
- **Preferencia de Sobreventa:** Servicio `StockManager` con bloqueo atómico de filas (`lockForUpdate()`) para evitar condiciones de carrera.
- **Agendador de Expiración (Laravel 12):** Comando de consola `ReleaseExpiredReservations` agendado cada minuto en `routes/console.php` para liberar stock de reservas expiradas tras 10 minutos.
- **Checkout Público y Confirmación:** Ruta pública de checkout y vista de confirmación protegida por middleware `signed` con expiración a los 30 minutos mediante `URL::temporarySignedRoute()`.
- **QA y Refactor SonarQube:** 489 tests integrados pasando en Pest. Complejidad cognitiva y excepciones customizadas en las acciones de dominio resueltas.

### Sprint 2.3 — Pagos con Stripe ✅

- **Estructura de Base de Datos Inmutable:** Creadas las tablas inmutables `payment` y `refund`, más la tabla `processed_webhook_event` para control de idempotencia de webhooks.
- **Abstracción del Gateway de Stripe:** Creado `StripeGateway` y `PaymentGatewayInterface` desacoplados de Eloquent mediante DTOs.
- **Idempotencia de Pagos y Reembolsos:** `InitiatePaymentAction` y `ProcessRefundAction` garantizan la prevención de cobros y reembolsos duplicados.
- **Manejo Seguro de Webhooks:** Endpoint público seguro exento de CSRF y multi-tenancy. El webhook verifica firmas digitales sobre el body crudo antes de procesar el cambio de estado.
- **Liberación de Stock automática:** Registrado el listener `ReleaseStockOnRefund` para liberar automáticamente el inventario de entradas al realizarse reembolsos totales.
- **QA Pipeline Completo:** 497 tests totales en Pest en verde con análisis estático impecable por PHPStan y formateo automático de Pint.

### Sprint 2.4 — Tickets PDF y QR ✅

- **Instalación de Bacon QR + DomPDF**: Bacon QR para la generación de códigos vectoriales y DomPDF para compilar PDFs de tickets.
- **Modelado e Idempotencia Fuerte**: Creación de la tabla `attendee` con claves únicas compuestas `(ticket_order_item_id, sequence)` para evitar asistentes duplicados.
- **Acciones y Tareas en Cola**: `GenerateAttendeesAction` ligera con estrategia de colisión finita de códigos. Despacho asíncrono asilado mediante `afterCommit()` en el listener.
- **Control de Concurrencia**: `SendTicketEmailJob` con claim atómico (`tickets_processing_at` con TTL de 15 minutos) que actualiza atómicamente a éxito `tickets_sent_at` y libera el semáforo.
- **Búsqueda e Invalidación de Enlaces (Magic Links)**: Búsqueda segura en `/my-orders` que envía un enlace temporal firmado (15 minutos) con token de un solo uso en caché. Consumo atómico mediante `Cache::pull()` e inicio de sesión de navegador.
- **QA y SonarQube**: Cobertura al 100% en verde (509 tests) con resolución de complejidad y excepciones de SonarQube.

### Sprint 3.1 — Check-in y Validación ✅

- **Modelos y Estructura**: Modelado e implementación de `CheckInList`, `CheckInLog` y `ActiveCheckIn` con SoftDeletes y claves únicas.
- **Lógica Transaccional**: `CheckInAttendeeAction` y `UndoCheckInAction` con transacciones de base de datos y control de concurrencia mediante bloqueos atómicos (`lockForUpdate`).
- **Validador Modular**: `ValidateQrCodeService` estructurado para baja complejidad cognitiva, sin code smells en SonarQube, controlando expiraciones, eventos correctos, duplicados y elegibilidad de producto.
- **Componentes Volt**:
  - `check-in.blade.php`: Lector QR basado en cámara web sin distorsión visual, detención automática al escanear y control de estados.
  - `attendee-list.blade.php`: Lista de invitados reactiva que se actualiza en segundo plano mediante eventos globales de Livewire (`check-in-updated`).
- **Políticas y Autorización**: `EventPolicy` configurada para controlar permisos de check-in y reversión según rol del organizador (`admin`, `editor`, `viewer`).
- **QA y Cobertura**: 35 tests Pest dedicados en verde y pipeline de QA completo libre de errores.

### Sprint 3.2 — Lista de Espera y Preguntas del Comprador ✅

- **Base de Datos y Unicidad Virtual:** Modelado de `waitlist_entry` y campos JSON para respuestas personalizadas (`custom_questions` en `event`, `custom_answers_staging` en `ticket_order_item` y `custom_answers` en `attendee`). Restricción `active_email_unique` dinámica (MySQL/SQLite) para unicidad activa.
- **Acciones Waitlist:** Actions transaccionales e idempotentes `JoinWaitlistAction`, `NotifyWaitlistAction`, `RollbackWaitlistReservationAction`, `ExpireWaitlistEntriesAction` y `ConvertWaitlistEntryAction`.
- **Automatización de Cola:** Evento post-commit `WaitlistEntryExpired` y listener `NotifyWaitlistOnExpiredListener` para notificar automáticamente al siguiente en cola al expirar.
- **StockManager:** Modificado para restar cupos de waitlist activos (`Notified`/`Reserved`) y soportar la exclusión del token en checkout.
- **Formularios de Checkout y Waitlist:** Formulario público Livewire Volt `join-waitlist` con rate-limiting compuesto. Checkout adaptado para consumir el token y validar dinámicamente en servidor las preguntas personalizadas.
- **Administración del Organizador:** Panel `custom-questions-management` para configurar preguntas con ordenamiento y IDs inmutables, y `waitlist-management` para gestionar colas manuales con bitácora de actividad (Activitylog).
- **QA e Integración:** 531 tests Pest en verde, PHPStan OK y Pint formateado.

### Sprint 3.4 — Panel de Evento Completo ✅

- **Dashboard de evento:** KPIs de ventas y operación, gráfico SVG de ventas diarias y refresco automático con `wire:poll.30s`.
- **Settings de evento:** formulario de configuración avanzada con plantillas de notificación y preferencias operativas.
- **API operativa:** `/api/v1/events/{event}/attendees`, `/check-in` y `/messages` con Sanctum, aislamiento de organizer y validación dedicada.
- **Verificación:** QA completo en verde antes de archivar el sprint.
- **Cierre formal:** Sprint archivado en `openspec/changes/archive/2026-07-07-sprint-3-4-panel-evento-completo/`.

### Sprint T0 — Multitenancy Foundation ✅

- `spatie/laravel-multitenancy` integrado en modo single DB con `Organizer` como tenant.
- Resolución tenant host-first con fallback por ruta interna y contexto global tenant-less para superadmin.
- Jobs tenant-aware con excepciones `NotTenantAware` donde el contexto se resuelve por ID propio.
- Sprint archivado en `openspec/changes/archive/2026-07-07-sprint-t0-multitenancy-foundation/`.

### Sprint 4.1 — Facturación ✅

- Base monetaria exacta para nuevos importes de facturación y reportes.
- Facturas automáticas con PDF descargable y numeración por organizador/año.
- Notas de crédito al procesar reembolsos.
- Billing settings y reportes de ingresos, impuestos y tarifas de plataforma con filtros y CSV.
- Sprint archivado en `openspec/changes/archive/2026-07-09-sprint-4-1-facturacion/`.

### Sprint 4.2 — Comisiones y Payouts (implementado) ✅

- Alcance cerrado a tracking interno de comisiones y payouts.
- No incluye transferencias reales ni Stripe Connect.
- Implementado en slices `4.2a` base y modelo, `4.2b` flujo y ajustes por refund, `4.2c` UX/reportes.
- La UX se inspiró en HI.EVENTS: tabs de settings, simulador de comisión, banner informativo y tablas filtrables con export CSV.

### Sprint 4.3 — Reportes Avanzados (implementado) ✅

- Alcance para organizer y admin/plataforma.
- Reportes read-only con 5 familias de reporte por scope, filtros por defecto de 90 días, tarjetas resumen, tablas y export CSV.
- UI inspirada en HI.EVENTS pero adaptada a Eventos, sin copia literal.
- **Estado actual**: Implementado, verificado y archivado. Slices `4.3a` (Foundation), `4.3b` (Organizer Reports) y `4.3c` (Platform Reports) operativos.

### Síntesis ejecutiva de Sprint 4.2

Sprint 4.2 cubrió la capa interna de monetización que faltaba entre facturación y reportes operativos. La base de comisiones y payout records (`4.2a`), el flujo de creación y ajuste por refunds (`4.2b`) y la experiencia de configuracion/reportes (`4.2c`) ya estan implementados. La trazabilidad de lo que se debe pagar y por que queda cerrada sin activar transferencias reales con Stripe Connect.

---

### Sprint 5.1 — Catálogo Público ✅

- Catálogo público implementado, verificado y archivado.
- El dominio raíz (`config('app.url')`) muestra el catálogo global.
- Cada dominio de organizer muestra solo sus eventos publicados.
- El acceso al checkout existente se reutiliza desde el detalle público.
- El header público ahora incluye acceso al login o Dashboard según el estado de autenticación.

### Sprint 5.2 — Búsqueda y Discovery UX ✅

- Implementada búsqueda híbrida con Laravel Scout + Meilisearch y fallback a Eloquent.
- Búsqueda textual sobre título y descripción.
- Filtros estructurados preservados: organizer, categoría, ciudad y fecha (como límite inferior inclusivo `From date`).
- Interfaz de descubrimiento componenteizada: `search-bar`, `filter-bar`, `filter-chip`, `result-summary` y `skeleton-card`.
- Indexación asíncrona automática tras commit para eventos `published` + `public`.
- Sprint implementado, corregido, verificado y archivado.

### Sprint 5.3 — SEO y Widget ✅

- URLs canónicas configuradas por `slug` con redirección automática HTTP 301 para IDs legacy.
- Metadatos SEO (Title, Description, Canonical, Open Graph, Twitter Cards) inyectados vía `@stack('seo')`.
- Sitemap XML público en `/sitemap.xml` para eventos publicados.
- Widget embebible con endpoint JSON (`/api/widget/events`) y script JS (`public/js/widget.js`) con soporte CORS.
- Todo testeado, verificado y archivado sin agregar dependencias de terceros.

### Sprint 5.4 — Rendimiento y Escalabilidad ✅

- Caché de Redis configurado como backend principal (`CACHE_STORE=redis`).
- Búsquedas fallback y metadatos cacheados con `Cache::tags(['catalog'])`.
- Invalidación de caché automática mediante Eventos Eloquent (`booted()` hooks en `Event`, `Category`, `Venue`).
- Agregados 7 índices de base de datos críticos faltantes (fechas, slugs, llaves foráneas).
- Solucionados N+1 query problems (eager loading `with()`) en `EventApiController` y `EventWidgetController`.
- Adapter S3 habilitado (`league/flysystem-aws-s3-v3`) para uso con MinIO local y S3 en producción.
- Endpoint de status profundo `/health` implementado mediante `spatie/laravel-health` (monitorea MySQL, Redis, Cache y Meilisearch) y devuelve HTTP 503 si falla un check crítico.
- Comando de Benchmark de catálogo (`php artisan catalog:benchmark`) creado para medir throughput de búsquedas.
- Object storage S3/MinIO habilitado para assets; la integración real con CDN queda diferida.
- Cursor pagination queda diferida a un sprint futuro.

### Sprint 6.1 — Backoffice de Plataforma ✅

- Implementado en el repositorio y archivado en `openspec/changes/archive/2026-07-16-sprint-6-1-backoffice/`.
- Incluye aislamiento global con `team_id: 0`, matriz `super_admin`/`platform_admin`, ciclo de usuarios con suspensión/restauración, moderación reversible de eventos, ajustes de plataforma con optimistic locking, fallback e historial de comisiones, API admin y UI Volt.
- Según el informe OpenSpec: 41/41 requisitos, 72/72 escenarios, 18/18 tareas, 928 tests, PHPStan/Pint/Rector limpios.
- Caveat formal: el informe declara “ready to archive”, pero el cambio archivado carece de `archive-report.md`; los resultados se documentan como evidencia reportada, no como verificación independiente rerun en esta actualización.

## Ciclo de vida y evidencia OpenSpec

| Estado | Cambios | Evidencia y caveat |
|---|---|---|
| Archivados/implementados | 13 cambios archivados, incluido Sprint 6.1 | El inventario confirma el archivo; algunos cambios históricos no tienen `verify-report.md` o `archive-report.md`. Sprint 4.3 conserva tareas incompletas; 4.2 y 5.1 tienen árboles anidados duplicados. |
| Activos con verificación | `mini-sprint-account-ux` | Informe PASS WITH WARNINGS, listo para archivar; 18/19 escenarios conformes y un caso parcialmente cubierto. |
| Activos con validación pendiente | `mini-sprint-responsive-ux` | Informe PASS, pero la validación visual/manual en navegador sigue sin marcarse como completada. |
| Activos con implementación sin cierre | `mini-sprint-email-verification-gate` | Tareas 15/15 marcadas; no hay `verify-report.md` ni `archive-report.md`. |
| Exploración solamente | `sprint-1-2-organizadores-y-equipos` | Solo existe `exploration.md`; no representa trabajo activo implementado. |

La configuración OpenSpec mantiene `testing.strict_tdd: true`; el informe de Sprint 6.1 declara, en cambio, modo Standard y strict TDD inactivo. Se conserva como caveat de proceso, no como resolución implícita.

**Con este Sprint, la Fase 5 queda oficialmente CERRADA.**

## Qué NO está hecho

- Sprint 6.2a: visibilidad global de auditoría de solo lectura, definida en OpenSpec y pendiente de implementación completa/verificación. La deuda conocida es la alineación del filtro global (`organizer_id IS NULL AND is_global = true`) con la documentación y la evidencia. GDPR, MFA, captura, esquema y backfill histórico permanecen fuera de este slice y requieren trabajo futuro separado.
- Sprint 6.3: webhooks outbound y documentación completa de API.
- Sprint 6.4: deployment, CI/CD, backups, Sentry, load testing y documentación final.
- Integración real con CDN y cursor pagination.
- Validación responsive manual en navegador y decisiones UX pendientes sobre notificaciones, feedback e iconos.
- Stripe Connect/KYC/transferencias reales, que permanecen fuera del alcance del tracking interno de comisiones.

El roadmap completo está en [`01-producto/PLAN_IMPLEMENTACION.md`](../01-producto/PLAN_IMPLEMENTACION.md).

---

## Bloqueos actuales

Limitaciones conocidas: CDN real y cursor pagination quedan diferidas; siguen pendientes Sprint 6.2a, GDPR/MFA, webhooks/documentación API, despliegue y cierre operativo. La validación responsive manual y las decisiones de notificaciones/iconos requieren trabajo explícito.

---

## Próximo paso

- Cerrar el ciclo de los cambios OpenSpec activos que ya tienen evidencia suficiente y abordar el slice read-only de Sprint 6.2a, manteniendo separado el trabajo futuro de GDPR/MFA/captura/esquema/backfill antes de iniciar 6.3 y 6.4.
