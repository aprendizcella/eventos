# Plan de Implementacion: Plataforma de Eventos y Ticketing

**Proyecto:** eventos — Plataforma de eventos y ticketing
**Stack:** Laravel 12 / PHP 8.4 / MariaDB 11 / Redis / Livewire + Volt / Tailwind CSS 4
**Duracion estimada:** 24 semanas (6 fases de 4 semanas)
**Metodologia:** Sprints de 1 semana con entregables verificables por fase
**Referencia:** Hi.Events (funcional), Attendize (ticketing), Eventbrite (benchmark)

> **Estado de ejecucion (actualizacion post Sprint 1.1):** Sprint 1.1 (Setup y Auth) esta **IMPLEMENTADO y verificado**. El stack base de Fase 1 (Laravel 12 / PHP 8.4 / Sail, Sanctum, Spatie Permission, Spatie Activitylog, Livewire + Volt, Mews/Purifier) esta instalado y operativo. Auth flows (registro, login, logout, reset de password), roles/permisos, audit logging, migraciones/seeders y tests en verde. Sprints 1.2-1.4 y Fases 2-6 siguen en planificacion. Vease el detalle en [Sprint 1.1](#sprint-11-setup-y-auth-semana-1--implementado).

---

## Tabla de contenidos

1. [Vision del producto](#1-vision-del-producto)
2. [Principios de implementacion](#2-principios-de-implementacion)
3. [Mapa de fases y sprints](#3-mapa-de-fases-y-sprints)
4. [Fase 1: Fundacion (Semanas 1-4)](#4-fase-1-fundacion-semanas-1-4)
5. [Fase 2: Ticketing y Compra (Semanas 5-8)](#5-fase-2-ticketing-y-compra-semanas-5-8)
6. [Fase 3: Operacion del Evento (Semanas 9-12)](#6-fase-3-operacion-del-evento-semanas-9-12)
7. [Fase 4: Monetizacion y Facturacion (Semanas 13-16)](#7-fase-4-monetizacion-y-facturacion-semanas-13-16)
8. [Fase 5: Discovery y Escalabilidad (Semanas 17-20)](#8-fase-5-discovery-y-escalabilidad-semanas-17-20)
9. [Fase 6: Administracion y Pulido (Semanas 21-24)](#9-fase-6-administracion-y-pulido-semanas-21-24)
10. [Dependencias entre fases](#10-dependencias-entre-fases)
11. [Criterios de calidad por sprint](#11-criterios-de-calidad-por-sprint)
12. [Riesgos y mitigaciones](#12-riesgos-y-mitigaciones)
13. [Metricas de progreso](#13-metricas-de-progreso)

---

## 1. Vision del producto

Construir una plataforma de eventos y ticketing self-hosted que permita:

- **Asistentes:** Descubrir eventos, comprar entradas, recibir tickets digitales, acceder con QR.
- **Organizadores:** Crear y gestionar eventos, configurar tipos de entrada, vender, gestionar asistentes, hacer check-in, ver metricas.
- **Administradores:** Gestionar usuarios, moderar eventos, configurar comisiones, ver metricas globales.

**No es:** Un marketplace tipo Eventbrite con efecto red. Es una plataforma que cada organizador puede desplegar y operar de forma independiente, con posibilidad de evolucionar a SaaS multi-tenant.

---

## 2. Principios de implementacion

### 2.1 Reglas de desarrollo

| Principio | Aplicacion |
|---|---|
| **SDD/TDD obligatorio** | No se implementa codigo sin spec previa. Tests antes que codigo. |
| **QA antes de commit** | Rector → Pint → PHPStan → Tests → SonarQube. Nada falla, nada se commitea. |
| **Commits atomicos** | Un commit = una unidad de trabajo. Feature, Fix o Chore. |
| **Acciones = casos de uso** | Cada accion de negocio es una clase en `app/Actions/`. |
| **DTOs para transporte** | FormRequest → toDto() → Controller → Action. Nunca `validated()` directo. |
| **Models = aggregates practicos** | Eloquent models como aggregate roots. Logica de dominio en Actions. |
| **Livewire = presentacion** | Componentes Volt para UI interactiva. No logica de negocio en componentes. |
| **API REST = integracion** | API versionada para mobile, webhooks, integraciones externas. |

### 2.2 Convenciones del boilerplate

| Convencion | Regla |
|---|---|
| Tablas | Singular (`event`, `order`, `product`) |
| PK | `{model}_id` (`event_id`, `order_id`) |
| FK | `{model}_id` (`organizer_id`, `event_id`) |
| SoftDeletes | Siempre en tablas nuevas |
| PHP | `declare(strict_types=1)` en cada archivo |
| Clases | `final` por defecto |
| Idioma codigo | Ingles (clases, metodos, variables, migraciones) |
| Comentarios | Espanol solo si son necesarios |
| Commits | Ingles (conventional commits) |

### 2.3 Flujo de trabajo por sprint

```
Dia 1: Spec (sdd-spec) + Design (sdd-design)
Dia 2-3: Tasks (sdd-tasks) + Implementacion (sdd-apply) con TDD
Dia 4: Verificacion (sdd-verify) + QA pipeline
Dia 5: Review + Archive (sdd-archive) + Retro
```

---

## 3. Mapa de fases y sprints

```
Fase 1: Fundacion          ████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  Semanas 1-4
Fase 2: Ticketing/Compra   ░░░░░░░░░░░░░░░░████████████░░░░░░░░░░░░░░░░░░░░  Semanas 5-8
Fase 3: Operacion          ░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████████░░░░░░░░  Semanas 9-12
Fase 4: Monetizacion       ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████░░  Semanas 13-16
Fase 5: Discovery          ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████  Semanas 17-20
Fase 6: Admin/Pulido       ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████  Semanas 21-24
```

### Resumen por fase

| Fase | Semanas | Sprints | Entregable principal | Librerias nuevas |
|---|---|---|---|---|
| 1. Fundacion | 1-4 | 4 | Auth, organizadores, eventos basicos, panel organizador | Sanctum, Permission, Activitylog, Purifier, Livewire, Volt |
| 2. Ticketing/Compra | 5-8 | 4 | Productos, checkout, Stripe, pedidos, tickets PDF | Stripe SDK, Bacon QR, DomPDF |
| 3. Operacion | 9-12 | 4 | Check-in, waitlist, preguntas, mensajes masivos, export | — |
| 4. Monetizacion | 13-16 | 4 | Facturas, reembolsos, comisiones, payouts, reportes | Horizon |
| 5. Discovery | 17-20 | 4 | Catalogo publico, busqueda, SEO, widget, CDN | Scout |
| 6. Admin/Pulido | 21-24 | 4 | Backoffice, audit, GDPR, MFA, webhooks, deploy | Deptrac (opcional) |

---

## 4. Fase 1: Fundacion (Semanas 1-4)

**Objetivo:** Base tecnica funcional con autenticacion, gestion de organizadores y eventos, y panel de organizador basico.

### Sprint 1.1: Setup y Auth (Semana 1) — IMPLEMENTADO

**Spec:** Configurar el stack base y sistema de autenticacion.
**Estado:** Completado y archivado en OpenSpec (`openspec/changes/archive/2026-06-25-sprint-1-1-setup-auth`).

| Tarea | Detalle | Entregable |
|---|---|---|
| 1.1.1 | Instalar librerias Fase 1 | `composer.json` actualizado |
| 1.1.2 | Configurar Sanctum (cookie + token) | `config/sanctum.php`, `config/cors.php` |
| 1.1.3 | Configurar Spatie Permission | Migraciones de roles/permisos |
| 1.1.4 | Configurar Spatie Activitylog | Migracion de `activity_log` |
| 1.1.5 | Configurar mews/purifier | `config/purifier.php` con perfiles |
| 1.1.6 | Instalar Livewire + Volt | `volt:install`, directorio `livewire/` |
| 1.1.7 | Crear modelo User con traits | `User.php` con HasApiTokens, HasRoles, LogsActivity |
| 1.1.8 | Crear actions de auth | `RegisterUser`, `LoginUser`, `RequestPasswordReset`, `VerifyEmail` |
| 1.1.9 | Crear componentes Volt de auth | `login.blade.php`, `register.blade.php`, `forgot-password.blade.php` |
| 1.1.10 | Crear layout base | `layouts/app.blade.php` con Tailwind |
| 1.1.11 | Tests de auth | Tests de registro, login, reset, email verification |
| 1.1.12 | Tests de roles/permisos | Tests de asignacion de roles, verificacion de permisos |

**Criterios de aceptacion:**
- [x] Usuario puede registrarse, verificar email, login, logout
- [x] Usuario puede solicitar reset de password
- [x] Roles y permisos funcionan (`hasRole`, `can`)
- [x] Actividad de auth se registra en `activity_log`
- [x] QA pipeline pasa limpio
- [x] SonarQube sin errores criticos

**Dependencias:** Ninguna. Es el primer sprint.

#### Estado de ejecucion (Sprint 1.1)

Stack y artefactos entregados en el repositorio:

| Area | Entregado real |
|---|---|
| Stack base | Laravel 12 / PHP 8.4 / Sail; `composer.json` con `laravel/sanctum ^4.3`, `spatie/laravel-permission ^8.0`, `spatie/laravel-activitylog ^5.0`, `mews/purifier ^3.4`, `livewire/livewire ^4.3`, `livewire/volt ^1.10` |
| Config publicada | `config/sanctum.php`, `config/permission.php`, `config/activitylog.php`, `config/purifier.php` |
| Migraciones | `personal_access_tokens_table`, `activity_log_table`, `permission_tables` (2026-06-23) |
| Modelo `User` | `HasApiTokens`, `HasRoles`, `LogsActivity`, `Notifiable`; implementa `MustVerifyEmail`; activity log privacy-safe (`logOnly(['name','email'])`, `logOnlyDirty`, log name `user`) |
| Actions de auth | `app/Actions/Auth/`: `RegisterUserAction`, `LoginUserAction`, `LogoutUserAction`, `RequestPasswordResetAction`, `ResetPasswordAction`, `RecordAuthActivityAction` |
| Seeders | `DatabaseSeeder`, `RoleSeeder` (seis roles: `super_admin`, `platform_admin`, `organizer_admin`, `organizer_editor`, `organizer_viewer`, `attendee`; idempotente via `firstOrCreate`) |
| Componentes Volt | `resources/views/livewire/auth/`: `login`, `register`, `forgot-password`, `reset-password` |
| Rutas (`routes/web.php`) | Volt routes `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` + POST controllers (`LoginController`, `RegisterController`, `LogoutController`, `RequestPasswordResetController`, `ResetPasswordController`) con throttle en login y reset request |
| Tests | `tests/Feature/Auth/` (Login, Logout, Register, PasswordReset, Throttling, AuthUi, AuthActionsGuardContract, AuthAuditSafety, PackageReadiness, UserReadiness), `tests/Feature/Audit/` (AuthAudit, AuthAuditFlow), `tests/Feature/Authorization/` (RoleMiddleware, RoleSeeder) |
| QA / SonarQube | `composer qa` (rector dry-run -> pint --dirty -> phpstan -> pest); `./sonar.sh`; coverage report path `build/logs/clover.xml` (`sonar-project.properties`) |

**Desviaciones respecto al plan (registradas):**

- Las Actions de auth usan sufijo `Action` (`RegisterUserAction`, etc.) en vez del nombre sin sufijo del plan. Convencion consistente en `app/Actions/Auth/`.
- Se anaden dos actions no previstas en la tabla: `LogoutUserAction` (logout explicito) y `ResetPasswordAction` (reset de password como accion propia), ademas de `RecordAuthActivityAction` para el audit logging de eventos de auth.
- La verificacion de email se delega al contrato `MustVerifyEmail` de Laravel + flow nativo en vez de una Action `VerifyEmail` dedicada.
- Sanctum funciona en modo cookie-based SPA (Livewire/Volt same-origin); el modo token-based queda disponible para la API futura. No se genero `config/cors.php` separado (Laravel 12 integra CORS en `bootstrap/app.php`).

---

### Sprint 1.2: Organizadores y Equipos (Semana 2)

**Spec:** Sistema de organizadores con gestion de equipos.

| Tarea | Detalle | Entregable |
|---|---|---|
| 1.2.1 | Migracion `organizer` | Tabla con slug, branding, config |
| 1.2.2 | Migracion `organizer_user` | Tabla pivot con rol (admin, editor, viewer) |
| 1.2.3 | Modelo Organizer | Con LogsActivity, relaciones |
| 1.2.4 | Acciones de organizador | `CreateOrganizer`, `UpdateOrganizer`, `AddTeamMember`, `RemoveTeamMember` |
| 1.2.5 | DTOs de organizador | `CreateOrganizerDto`, `UpdateOrganizerDto` |
| 1.2.6 | FormRequests | `StoreOrganizerRequest`, `UpdateOrganizerRequest` |
| 1.2.7 | Componentes Volt de organizador | `organizer-dashboard`, `organizer-settings`, `team-management` |
| 1.2.8 | Policies | `OrganizerPolicy` |
| 1.2.9 | Roles por organizador | `organizer_admin`, `organizer_editor`, `organizer_viewer` |
| 1.2.10 | Tests de organizador | CRUD, team management, policies |

**Criterios de aceptacion:**
- [ ] Usuario puede crear un organizador
- [ ] Usuario puede invitar miembros al equipo con rol
- [ ] Solo admin puede anadir/eliminar miembros
- [ ] Dashboard de organizador muestra info basica
- [ ] Settings de organizador (nombre, logo, color) editables
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.1 (User, Auth, Permission).

---

### Sprint 1.3: Eventos Basicos (Semana 3)

**Spec:** CRUD de eventos con configuracion basica.

| Tarea | Detalle | Entregable |
|---|---|---|
| 1.3.1 | Migracion `category` | Tabla con nombre, slug, padre |
| 1.3.2 | Migracion `venue` | Tabla con direccion, ciudad, pais, coordenadas, capacidad |
| 1.3.3 | Migracion `event` | Tabla con todos los campos basicos |
| 1.3.4 | Enum `EventStatus` | draft, configured, published, paused, completed, cancelled |
| 1.3.5 | Enum `EventVisibility` | public, private, password_protected |
| 1.3.6 | Modelos Category, Venue, Event | Con LogsActivity, relaciones, casts |
| 1.3.7 | Acciones de evento | `CreateEvent`, `UpdateEvent`, `PublishEvent`, `CancelEvent` |
| 1.3.8 | DTOs de evento | `CreateEventDto`, `UpdateEventDto` |
| 1.3.9 | FormRequests | `StoreEventRequest`, `UpdateEventRequest` |
| 1.3.10 | Componentes Volt de evento | `event-form`, `event-list`, `event-detail` |
| 1.3.11 | Policy `EventPolicy` | Permisos por rol de organizador |
| 1.3.12 | Tests de evento | CRUD, publish, cancel, policies |

**Criterios de aceptacion:**
- [ ] Organizador puede crear evento borrador
- [ ] Organizador puede editar evento (detalles, venue, categoria)
- [ ] Organizador puede publicar evento (solo si esta configurado)
- [ ] Organizador puede cancelar evento
- [ ] Lista de eventos del organizador con filtros
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.2 (Organizer).

---

### Sprint 1.4: Panel de Organizador (Semana 4)

**Spec:** Dashboard del organizador con metricas basicas y navegacion.

| Tarea | Detalle | Entregable |
|---|---|---|
| 1.4.1 | Layout de organizador | `layouts/organizer.blade.php` con sidebar |
| 1.4.2 | Dashboard metricas | Ventas totales, asistentes, eventos activos |
| 1.4.3 | Navegacion del panel | Sidebar con enlaces a secciones |
| 1.4.4 | Componente `event-dashboard` | KPIs del evento, estado, tareas pendientes |
| 1.4.5 | API routes basicas | `/api/v1/organizers`, `/api/v1/events` |
| 1.4.6 | API Resources | `OrganizerResource`, `EventResource` |
| 1.4.7 | Middleware de contexto | `OrganizerContext` establece organizer actual |
| 1.4.8 | Tests de panel | Dashboard, metricas, API |
| 1.4.9 | Documentacion API | OpenAPI spec basica |
| 1.4.10 | Retro de Fase 1 | Review de lo construido, ajustes |

**Criterios de aceptacion:**
- [ ] Dashboard muestra metricas reales (aunque sean 0)
- [ ] Navegacion funcional entre secciones
- [ ] API REST basica funcional con auth Sanctum
- [ ] Middleware de contexto funciona
- [ ] QA pipeline pasa limpio
- [ ] Fase 1 completa: Auth + Organizer + Event + Panel

**Dependencias:** Sprints 1.1, 1.2, 1.3.

---

## 5. Fase 2: Ticketing y Compra (Semanas 5-8)

**Objetivo:** Venta de entradas con checkout funcional, pago por Stripe y generacion de tickets PDF con QR.

### Sprint 2.1: Productos y Tipos de Entrada (Semana 5)

| Tarea | Detalle | Entregable |
|---|---|---|
| 2.1.1 | Migracion `product` | Tipos de entrada, add-ons, merch |
| 2.1.2 | Migracion `product_price` | Tiers de precio, quotas |
| 2.1.3 | Migracion `promo_code` | Codigos promocionales |
| 2.1.4 | Enum `ProductType` | ticket, addon, merchandise, donation |
| 2.1.5 | Enum `PromoCodeType` | percentage, fixed |
| 2.1.6 | Modelos Product, ProductPrice, PromoCode | Con LogsActivity |
| 2.1.7 | Acciones de producto | `CreateProduct`, `UpdateProduct`, `SetProductPricing`, `CreatePromoCode` |
| 2.1.8 | Servicio `PriceCalculator` | Calcula precios con taxes y descuentos |
| 2.1.9 | Servicio `PromoCodeValidator` | Valida aplicabilidad de promo codes |
| 2.1.10 | Componentes Volt de producto | `product-list`, `product-form`, `product-pricing` |
| 2.1.11 | Tests de producto | CRUD, pricing, promo codes |

**Criterios de aceptacion:**
- [ ] Organizador puede crear tipos de entrada con precios y quotas
- [ ] Organizador puede configurar multiples tiers por producto
- [ ] Organizador puede crear promo codes con reglas
- [ ] PriceCalculator calcula correctamente subtotal, taxes, descuentos
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.3 (Event).

---

### Sprint 2.2: Ordenes y Checkout (Semana 6)

| Tarea | Detalle | Entregable |
|---|---|---|
| 2.2.1 | Migracion `order` | Pedidos con estados y totales |
| 2.2.2 | Migracion `order_item` | Lineas de pedido |
| 2.2.3 | Enum `OrderStatus` | pending, reserved, paid, confirmed, cancelled, expired, refunded |
| 2.2.4 | Modelo Order, OrderItem | Con LogsActivity, calculo de totales |
| 2.2.5 | Servicio `StockManager` | Reserva/libera stock atomicamente (Redis lock) |
| 2.2.6 | Acciones de orden | `CreateOrder`, `ReserveStock`, `ApplyPromoCode`, `ProcessCheckout`, `CancelOrder`, `ReleaseExpiredReservations` |
| 2.2.7 | DTOs de orden | `CreateOrderDto`, `OrderItemDto` |
| 2.2.8 | Command de liberacion | `ReleaseExpiredReservations` via scheduler (cada minuto) |
| 2.2.9 | Componente Volt `checkout` | Flujo de compra paso a paso |
| 2.2.10 | Componente Volt `order-confirmation` | Confirmacion post-compra |
| 2.2.11 | Tests de checkout | Creacion, reserva, expiracion, cancelacion |
| 2.2.12 | Tests de concurrencia | Overselling prevention |

**Criterios de aceptacion:**
- [ ] Asistente puede seleccionar entradas y crear pedido
- [ ] Stock se reserva con TTL de 10 minutos
- [ ] Si expira, se libera automaticamente
- [ ] Promo code se aplica correctamente
- [ ] Tests de concurrencia pasan (no overselling)
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.1 (Product, Pricing).

---

### Sprint 2.3: Pagos con Stripe (Semana 7)

| Tarea | Detalle | Entregable |
|---|---|---|
| 2.3.1 | Instalar Stripe SDK | `composer require stripe/stripe-php` |
| 2.3.2 | Migracion `payment` | Pagos con provider_id, status |
| 2.3.3 | Migracion `refund` | Reembolsos |
| 2.3.4 | Enum `PaymentStatus` | pending, completed, failed, refunded, partially_refunded |
| 2.3.5 | Enum `PaymentMethod` | stripe, paypal, offline |
| 2.3.6 | Modelos Payment, Refund | Con LogsActivity |
| 2.3.7 | Interface `PaymentGatewayInterface` | Contrato para gateways |
| 2.3.8 | Implementacion `StripeGateway` | PaymentIntent, webhook, refund |
| 2.3.9 | Acciones de pago | `InitiatePayment`, `HandleStripeWebhook`, `ProcessRefund` |
| 2.3.10 | Endpoint webhook | `/api/v1/webhooks/stripe` con firma HMAC |
| 2.3.11 | Domain events | `PaymentCompleted`, `PaymentFailed`, `RefundProcessed` |
| 2.3.12 | Listeners | `ConfirmOrderOnPaymentCompleted`, `NotifyOnPaymentFailed` |
| 2.3.13 | Tests de pago | PaymentIntent, webhook, refund |

**Criterios de aceptacion:**
- [ ] Checkout redirige a Stripe Checkout o usa Elements
- [ ] Webhook de Stripe confirma pago y actualiza orden
- [ ] Orden pasa a `confirmed` tras pago exitoso
- [ ] Reembolso funciona (total y parcial)
- [ ] Webhook verificado con firma HMAC
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.2 (Order, Checkout).

---

### Sprint 2.4: Tickets PDF y QR (Semana 8)

| Tarea | Detalle | Entregable |
|---|---|---|
| 2.4.1 | Instalar Bacon QR + DomPDF | `composer require bacon/bacon-qr-code barryvdh/laravel-dompdf` |
| 2.4.2 | Migracion `attendee` | Asistentes con unique_code, status |
| 2.4.3 | Enum `AttendeeStatus` | active, cancelled, checked_in |
| 2.4.4 | Modelo Attendee | Con LogsActivity, generacion de unique_code |
| 2.4.5 | Servicio `QrCodeGenerator` | Genera QR como PNG/SVG |
| 2.4.6 | Servicio `TicketPdfGenerator` | Genera PDF con branding del organizador |
| 2.4.7 | Accion `GenerateAttendeeQr` | Genera codigo QR para cada attendee |
| 2.4.8 | Listener `GenerateAttendeesOnOrderConfirmed` | Crea attendees tras confirmacion |
| 2.4.9 | Email de confirmacion | Template con PDF adjunto |
| 2.4.10 | Componente Volt `my-orders` | Historial de compras del asistente |
| 2.4.11 | Tests de tickets | Generacion QR, PDF, email |
| 2.4.12 | Retro de Fase 2 | Review de lo construido, ajustes |

**Criterios de aceptacion:**
- [ ] Tras pago confirmado, se generan attendees
- [ ] Cada attendee tiene unique_code y QR
- [ ] PDF de ticket se genera con branding del organizador
- [ ] Email de confirmacion llega con PDF adjunto
- [ ] Asistente ve sus pedidos y descarga tickets
- [ ] QA pipeline pasa limpio
- [ ] Fase 2 completa: Productos + Checkout + Stripe + Tickets

**Dependencias:** Sprints 2.1, 2.2, 2.3.

---

## 6. Fase 3: Operacion del Evento (Semanas 9-12)

**Objetivo:** Check-in, gestion de asistentes, herramientas operativas para el dia del evento.

### Sprint 3.1: Check-in y Validacion (Semana 9)

| Tarea | Detalle | Entregable |
|---|---|---|
| 3.1.1 | Migracion `check_in_list` | Listas de check-in por evento |
| 3.1.2 | Acciones de check-in | `CheckInAttendee`, `UndoCheckIn` |
| 3.1.3 | Servicio de validacion QR | Verifica unique_code, status, evento |
| 3.1.4 | Componente Volt `check-in` | Escaneo QR con camara (JS) |
| 3.1.5 | Componente Volt `attendee-list` | Lista de asistentes con busqueda |
| 3.1.6 | Domain events | `AttendeeCheckedIn`, `CheckInRejected` |
| 3.1.7 | Tests de check-in | Validacion QR, undo, lista |

**Criterios de aceptacion:**
- [ ] Escaner lee QR y valida entrada
- [ ] QR usado se marca como checked_in
- [ ] QR ya usado rechaza entrada
- [ ] Undo check-in funciona
- [ ] Lista de asistentes con busqueda por nombre/email
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.4 (Attendee).

---

### Sprint 3.2: Waitlist y Preguntas (Semana 10)

| Tarea | Detalle | Entregable |
|---|---|---|
| 3.2.1 | Migracion `waitlist_entry` | Lista de espera con posicion |
| 3.2.2 | Enum `WaitlistStatus` | waiting, notified, expired, converted |
| 3.2.3 | Modelo WaitlistEntry | Con LogsActivity |
| 3.2.4 | Acciones de waitlist | `JoinWaitlist`, `NotifyWaitlist`, `ConvertWaitlistEntry` |
| 3.2.5 | Listener `NotifyWaitlistOnProductSoldOut` | Notifica al siguiente |
| 3.2.6 | Campo `custom_questions` en event | JSON con preguntas de registro |
| 3.2.7 | Campo `custom_answers` en attendee | JSON con respuestas |
| 3.2.8 | Componentes Volt de waitlist | Formulario de union, gestion |
| 3.2.9 | Tests de waitlist | Union, notificacion, conversion |

**Criterios de aceptacion:**
- [ ] Asistente puede unirse a waitlist si evento agotado
- [ ] Cuando hay plaza, se notifica al siguiente
- [ ] Preguntas personalizadas en checkout
- [ ] Respuestas se almacenan en attendee
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.1 (Product), Sprint 2.4 (Attendee).

---

### Sprint 3.3: Mensajes Masivos y Export (Semana 11)

| Tarea | Detalle | Entregable |
|---|---|---|
| 3.3.1 | Migracion `notification_template` | Plantillas de email |
| 3.3.2 | Migracion `notification_log` | Log de envios |
| 3.3.3 | Acciones de notificacion | `SendBulkMessage`, `SendEventReminder` |
| 3.3.4 | Job `SendBulkEmailJob` | Envio masivo via colas |
| 3.3.5 | Servicio `ExportAttendeeList` | Export CSV/XLSX |
| 3.3.6 | Componente Volt `bulk-message` | Formulario de mensaje masivo |
| 3.3.7 | Componente Volt `export-attendees` | Boton de export con filtros |
| 3.3.8 | Tests de mensajes | Envio masivo, templates, export |

**Criterios de aceptacion:**
- [ ] Organizador puede enviar email masivo a asistentes
- [ ] Mensajes se procesan via cola (no bloquean)
- [ ] Export CSV/XLSX funciona con filtros
- [ ] Log de envios disponible
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 3.1 (Attendee).

---

### Sprint 3.4: Panel de Evento Completo (Semana 12)

| Tarea | Detalle | Entregable |
|---|---|---|
| 3.4.1 | Componente `event-dashboard` completo | KPIs, ventas en tiempo real, tareas |
| 3.4.2 | Componente `event-settings` | Configuracion avanzada (SEO, emails, waitlist) |
| 3.4.3 | Componente `sales-overview` | Grafico de ventas diarias |
| 3.4.4 | API routes de operacion | `/api/v1/events/{event}/attendees`, `/check-in`, `/messages` |
| 3.4.5 | Tests de panel | Dashboard, settings, API |
| 3.4.6 | Retro de Fase 3 | Review de lo construido, ajustes |

**Criterios de aceptacion:**
- [ ] Dashboard muestra ventas en tiempo real
- [ ] Settings de evento configurables
- [ ] API de operacion completa
- [ ] QA pipeline pasa limpio
- [ ] Fase 3 completa: Check-in + Waitlist + Mensajes + Panel

**Dependencias:** Sprints 3.1, 3.2, 3.3.

---

## 7. Fase 4: Monetizacion y Facturacion (Semanas 13-16)

**Objetivo:** Facturacion, reembolsos, comisiones de plataforma, payouts a organizadores, reportes avanzados.

### Sprint 4.1: Facturacion (Semana 13)

| Tarea | Detalle | Entregable |
|---|---|---|
| 4.1.1 | Migracion `invoice` | Facturas con numero secuencial |
| 4.1.2 | Servicio `InvoicePdfGenerator` | PDF de factura |
| 4.1.3 | Acciones de factura | `GenerateInvoice`, `IssueCreditNote` |
| 4.1.4 | Listener `GenerateInvoiceOnOrderConfirmed` | Auto-genera factura |
| 4.1.5 | Componente Volt `invoice-download` | Descarga PDF |
| 4.1.6 | Tests de facturacion | Generacion, nota de credito, PDF |

**Criterios de aceptacion:**
- [ ] Factura se genera automaticamente tras pago
- [ ] Numero secuencial e inmutable
- [ ] PDF descargable
- [ ] Nota de credito en reembolso
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.3 (Payment), Sprint 2.4 (Attendee).

---

### Sprint 4.2: Comisiones y Payouts (Semana 14)

| Tarea | Detalle | Entregable |
|---|---|---|
| 4.2.1 | Migracion `payout` | Payouts a organizadores |
| 4.2.2 | Servicio `CommissionCalculator` | Calcula comisiones de plataforma |
| 4.2.3 | Acciones de payout | `CalculatePayout`, `ProcessPayout` |
| 4.2.4 | Stripe Connect | Payouts automaticos a organizadores |
| 4.2.5 | Configuracion de comisiones | Porcentaje + fee fijo por ticket |
| 4.2.6 | Tests de comisiones | Calculo correcto, payout |

**Criterios de aceptacion:**
- [ ] Comision se calcula correctamente en cada pedido
- [ ] Payout se genera periodicamente
- [ ] Stripe Connect transfiere a organizador
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 4.1 (Invoice), Sprint 2.3 (Stripe).

---

### Sprint 4.3: Reportes Avanzados (Semana 15)

| Tarea | Detalle | Entregable |
|---|---|---|
| 4.3.1 | Servicio `ReportGenerator` | Genera reportes de ventas, asistentes |
| 4.3.2 | Componente `sales-report` | Informe de ventas por periodo |
| 4.3.3 | Componente `attendee-report` | Informe de asistentes |
| 4.3.4 | Componente `dashboard-metrics` | Metricas avanzadas |
| 4.3.5 | Export de reportes | PDF, CSV, XLSX |
| 4.3.6 | Tests de reportes | Generacion, export |

**Criterios de aceptacion:**
- [ ] Reporte de ventas por dia/semana/mes
- [ ] Reporte de asistentes con filtros
- [ ] Metricas de dashboard actualizadas
- [ ] Export funciona
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 4.1 (Invoice), Sprint 4.2 (Payout).

---

### Sprint 4.4: Retro y Ajustes (Semana 16)

| Tarea | Detalle | Entregable |
|---|---|---|
| 4.4.1 | Instalar Laravel Horizon | Monitor de colas Redis |
| 4.4.2 | Optimizacion de colas | Priorizacion de jobs |
| 4.4.3 | Retro de Fase 4 | Review de lo construido |
| 4.4.4 | Ajustes y fixes | Issues pendientes |

**Criterios de aceptacion:**
- [ ] Horizon operativo
- [ ] Colas optimizadas
- [ ] QA pipeline pasa limpio
- [ ] Fase 4 completa: Facturacion + Comisiones + Reportes

**Dependencias:** Sprints 4.1, 4.2, 4.3.

---

## 8. Fase 5: Discovery y Escalabilidad (Semanas 17-20)

**Objetivo:** Catalogo publico, busqueda, SEO, widget embebible, optimizacion de rendimiento.

### Sprint 5.1: Catalogo Publico (Semana 17)

| Tarea | Detalle | Entregable |
|---|---|---|
| 5.1.1 | Layout publico | `layouts/public.blade.php` |
| 5.1.2 | Componente `event-list-public` | Listado publico con filtros |
| 5.1.3 | Componente `event-detail-public` | Pagina publica del evento |
| 5.1.4 | Componente `event-card` | Card reutilizable |
| 5.1.5 | Filtros por categoria, ciudad, fecha | Busqueda basica |
| 5.1.6 | Tests de catalogo | Listado, detalle, filtros |

**Criterios de aceptacion:**
- [ ] Pagina publica lista eventos con filtros
- [ ] Detalle de evento visible sin login
- [ ] Compra desde pagina publica
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.3 (Event), Sprint 2.4 (Attendee).

---

### Sprint 5.2: Busqueda con Meilisearch (Semana 18)

| Tarea | Detalle | Entregable |
|---|---|---|
| 5.2.1 | Instalar Laravel Scout | `composer require laravel/scout` |
| 5.2.2 | Configurar Meilisearch driver | `config/scout.php` |
| 5.2.3 | Modelo Event con Searchable | Indexacion automatica |
| 5.2.4 | Busqueda full-text | Texto, categoria, ciudad, fecha |
| 5.2.5 | Facets y filtros | Meilisearch facets |
| 5.2.6 | Tests de busqueda | Indexacion, busqueda, facets |

**Criterios de aceptacion:**
- [ ] Busqueda full-text funcional
- [ ] Filtros por categoria, ciudad, fecha
- [ ] Resultados ordenados por relevancia/fecha
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 5.1 (Catalogo).

---

### Sprint 5.3: SEO y Widget (Semana 19)

| Tarea | Detalle | Entregable |
|---|---|---|
| 5.3.1 | SEO meta tags | Title, description, OG, Twitter |
| 5.3.2 | Sitemap XML | Generacion automatica |
| 5.3.3 | URLs amigables | Slugs por evento |
| 5.3.4 | Widget embebible | JS snippet para webs externas |
| 5.3.5 | API publica del widget | `/api/v1/events/{slug}/widget` |
| 5.3.6 | Tests de SEO | Meta tags, sitemap, widget |

**Criterios de aceptacion:**
- [ ] Meta tags correctos en cada pagina
- [ ] Sitemap.xml generado
- [ ] URLs amigables con slug
- [ ] Widget embebible funcional
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 5.2 (Busqueda).

---

### Sprint 5.4: Optimizacion de Rendimiento (Semana 20)

| Tarea | Detalle | Entregable |
|---|---|---|
| 5.4.1 | Cache Redis | Cache de lecturas frecuentes |
| 5.4.2 | Optimizacion de queries | Indexes, eager loading |
| 5.4.3 | CDN para assets | MinIO como S3-compatible |
| 5.4.4 | Paginacion optimizada | Cursor pagination |
| 5.4.5 | Health checks | `/up`, `/health` |
| 5.4.6 | Tests de rendimiento | Load testing basico |
| 5.4.7 | Retro de Fase 5 | Review de lo construido |

**Criterios de aceptacion:**
- [ ] Cache activo para lecturas
- [ ] Queries optimizadas (sin N+1)
- [ ] Assets servidos via CDN
- [ ] Health checks funcionales
- [ ] QA pipeline pasa limpio
- [ ] Fase 5 completa: Catalogo + Busqueda + SEO + Rendimiento

**Dependencias:** Sprints 5.1, 5.2, 5.3.

---

## 9. Fase 6: Administracion y Pulido (Semanas 21-24)

**Objetivo:** Backoffice de plataforma, auditoria, GDPR, MFA, webhooks outbound, deploy en produccion.

### Sprint 6.1: Backoffice de Plataforma (Semana 21)

| Tarea | Detalle | Entregable |
|---|---|---|
| 6.1.1 | Layout de admin | `layouts/admin.blade.php` |
| 6.1.2 | Componente `admin-dashboard` | Metricas globales |
| 6.1.3 | Componente `user-management` | Gestion de usuarios |
| 6.1.4 | Componente `moderate-events` | Moderacion de eventos |
| 6.1.5 | Componente `platform-settings` | Configuracion global |
| 6.1.6 | API routes de admin | `/api/v1/admin/*` |
| 6.1.7 | Tests de admin | CRUD, moderacion, settings |

**Criterios de aceptacion:**
- [ ] Admin puede gestionar usuarios
- [ ] Admin puede moderar eventos
- [ ] Configuracion de comisiones global
- [ ] Metricas globales visibles
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.1 (User, Permission).

---

### Sprint 6.2: Audit, GDPR y MFA (Semana 22)

| Tarea | Detalle | Entregable |
|---|---|---|
| 6.2.1 | Panel de actividad | Visualizacion de `activity_log` |
| 6.2.2 | Export de datos personales | GDPR: descargar datos del usuario |
| 6.2.3 | Derecho al olvido | GDPR: anonymize + soft delete |
| 6.2.4 | MFA TOTP | RFC 6238, QR setup |
| 6.2.5 | Acciones de MFA | `EnableMfa`, `DisableMfa`, `VerifyMfa` |
| 6.2.6 | Tests de GDPR | Export, delete, anonymize |
| 6.2.7 | Tests de MFA | Setup, verify, disable |

**Criterios de aceptacion:**
- [ ] Audit log visible en admin
- [ ] Usuario puede exportar sus datos
- [ ] Usuario puede solicitar eliminacion
- [ ] MFA funcional para organizadores
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 6.1 (Admin), Sprint 1.1 (User).

---

### Sprint 6.3: Webhooks Outbound y Documentacion (Semana 23)

| Tarea | Detalle | Entregable |
|---|---|---|
| 6.3.1 | Migracion `webhook` | Webhooks por organizador |
| 6.3.2 | Migracion `webhook_delivery` | Log de entregas |
| 6.3.3 | Servicio `WebhookDispatcher` | Envio de webhooks firmados |
| 6.3.4 | Configuracion de webhooks | UI para gestionar webhooks |
| 6.3.5 | Documentacion API completa | OpenAPI/Swagger |
| 6.3.6 | Documentacion de desarrollo | README, CONTRIBUTING, arquitectura |
| 6.3.7 | Tests de webhooks | Envio, firma, reintento |

**Criterios de aceptacion:**
- [ ] Organizador puede configurar webhooks
- [ ] Webhooks se envian firmados con HMAC
- [ ] Reintentos con backoff exponencial
- [ ] Documentacion API completa
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 6.1 (Admin).

---

### Sprint 6.4: Deploy y Cierre (Semana 24)

| Tarea | Detalle | Entregable |
|---|---|---|
| 6.4.1 | Configuracion de produccion | `.env.production`, variables |
| 6.4.2 | CI/CD pipeline | GitHub Actions: test, build, deploy |
| 6.4.3 | Backups automaticos | Database + storage |
| 6.4.4 | Monitoring | Sentry configurado |
| 6.4.5 | Load testing | Pruebas de carga |
| 6.4.6 | Documentacion final | Manual de usuario, admin, deploy |
| 6.4.7 | Retro final | Review de todo el proyecto |

**Criterios de aceptacion:**
- [ ] Deploy automatico via CI/CD
- [ ] Backups configurados
- [ ] Sentry operativo
- [ ] Load testing pasado
- [ ] Documentacion completa
- [ ] QA pipeline pasa limpio
- [ ] **Proyecto completo**

**Dependencias:** Sprints 6.1, 6.2, 6.3.

---

## 10. Dependencias entre fases

```
Fase 1 (Fundacion)
    │
    ├──→ Fase 2 (Ticketing/Compra)
    │       │
    │       ├──→ Fase 3 (Operacion)
    │       │       │
    │       │       ├──→ Fase 4 (Monetizacion)
    │       │       │       │
    │       │       │       └──→ Fase 5 (Discovery) ──→ Fase 6 (Admin/Pulido)
    │       │       │
    │       │       └──→ Fase 5 (Discovery) [parcial, necesita Evento]
    │       │
    │       └──→ Fase 4 (Monetizacion) [parcial, necesita Payment]
    │
    └──→ Fase 5 (Discovery) [parcial, necesita Event]
    └──→ Fase 6 (Admin) [parcial, necesita User]
```

### Dependencias criticas (bloqueantes)

| Fase | Bloqueada por | Razon |
|---|---|---|
| Fase 2 | Fase 1 | Necesita Event + Organizer para crear productos |
| Fase 3 | Fase 2 | Necesita Attendee + Order para check-in |
| Fase 4 | Fase 2 | Necesita Payment + Order para facturacion |
| Fase 5 | Fase 1 | Necesita Event para catalogo publico |
| Fase 6 | Fase 1 | Necesita User + Permission para admin |

---

## 11. Criterios de calidad por sprint

### Pipeline QA obligatorio (cada sprint)

```bash
# Antes de cada commit:
composer qa
# Ejecuta: rector → pint → phpstan → test

# Despues de qa:
./sonar.sh
# Verifica SonarQube sin errores criticos
```

### Metricas minimas por sprint

| Metrica | Umbral |
|---|---|
| PHPStan level | 8 (sin errores) |
| Coverage minimo | 80% (nuevos archivos) |
| SonarQube | Sin bugs criticos, sin vulnerabilities |
| Tests | Todos en verde |
| Rector | Sin cambios pendientes |
| Pint | Sin cambios de formato |

### Checklist de cierre de sprint

- [ ] Todos los tests pasan
- [ ] PHPStan level 8 limpio
- [ ] Pint sin cambios pendientes
- [ ] Rector sin cambios pendientes
- [ ] SonarQube sin errores criticos
- [ ] Documentacion actualizada
- [ ] Commits atomicos y descriptivos
- [ ] Spec del sprint archivada (sdd-archive)

---

## 12. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion | Fase |
|---|---|---|---|---|
| Overselling (vender mas que capacidad) | Alta | Critico | Redis lock + SELECT FOR UPDATE + tests de concurrencia | 2 |
| Webhooks perdidos (Stripe) | Media | Alto | Reintentos con backoff + reconciliacion periodica | 2 |
| Reservas fantasma (stock no liberado) | Media | Alto | TTL estricto + scheduler cada minuto + monitor | 2 |
| HTMLPurifier deprecations PHP 8.5+ | Media | Medio | Monitorizar releases. Plan de migracion a custom. | 1 |
| Livewire v4 breaking changes | Baja | Medio | Pin version. Leer changelog antes de actualizar. | 1 |
| Scope creep (anadir funcionalidad sin control) | Alta | Alto | Roadmap por fases. MVP claro. Backlog priorizado. | Todas |
| Complejidad DDD prematura | Media | Medio | Solo bounded contexts necesarios por fase. | 1-3 |
| Rendimiento en venta masiva | Media | Alto | Redis queue, CDN, cache, paginacion, indexes. | 5 |
| GDPR compliance | Media | Alto | Export de datos, derecho al olvido, consentimiento. | 6 |
| Fraude en pagos | Media | Alto | Stripe Radar, rate limiting, monitor de anomalias. | 2 |

---

## 13. Metricas de progreso

### Seguimiento por fase

| Fase | Semanas | Sprints | Tareas totales | Completadas | % |
|---|---|---|---|---|---|
| 1. Fundacion | 1-4 | 4 | ~50 | ~12 (Sprint 1.1) | ~24% |
| 2. Ticketing/Compra | 5-8 | 4 | ~50 | 0 | 0% |
| 3. Operacion | 9-12 | 4 | ~35 | 0 | 0% |
| 4. Monetizacion | 13-16 | 4 | ~30 | 0 | 0% |
| 5. Discovery | 17-20 | 4 | ~30 | 0 | 0% |
| 6. Admin/Pulido | 21-24 | 4 | ~35 | 0 | 0% |
| **Total** | **24** | **24** | **~230** | **~12** | **~5%** |

### Indicadores de salud del proyecto

| Indicador | Objetivo | Actual |
|---|---|---|
| Tests passing | 100% | Verde (Sprint 1.1) |
| Coverage | >80% | En umbral Sprint 1.1; coverage report en `build/logs/clover.xml` |
| PHPStan level | 8 | Limpio (Sprint 1.1) |
| SonarQube quality gate | Pass | Sin errores criticos (Sprint 1.1) |
| Technical debt ratio | <5% | — |
| Bugs abiertos | 0 al cierre de sprint | 0 (Sprint 1.1 cerrado) |

---

## Apendice A: Estructura de archivos por sprint

Cada sprint genera archivos en las siguientes ubicaciones:

```
app/
├── Actions/{Dominio}/          # Acciones del sprint
├── DataTransferObjects/{Dominio}/  # DTOs del sprint
├── Enums/                      # Enums nuevos
├── Events/{Dominio}/           # Domain events
├── Listeners/{Dominio}/        # Event listeners
├── Models/                     # Nuevos modelos
├── Http/
│   ├── Controllers/{Dominio}/  # Controllers
│   ├── Requests/{Dominio}/     # FormRequests
│   └── Resources/{Dominio}/    # API Resources
├── ViewModels/{Dominio}/       # ViewModels
├── Repositories/{Dominio}/     # Repositories (si aplica)
├── Services/                   # Servicios transversales
├── Policies/                   # Authorization policies
├── Rules/                      # Custom validation rules
└── Middleware/                 # Middleware nuevos

database/migrations/            # Migraciones del sprint
database/factories/             # Factories nuevos
database/seeders/               # Seeders nuevos

resources/views/
├── livewire/{Dominio}/         # Componentes Volt
├── layouts/                    # Layouts nuevos
└── components/                 # Componentes Blade

routes/                         # Rutas nuevas
tests/                          # Tests del sprint
```

---

## Apendice B: Comandos utiles por sprint

```bash
# Crear nuevo modulo (ejemplo: Event)
vendor/bin/sail artisan make:model Event -mfs  # model + migration + factory + seeder
vendor/bin/sail artisan make:class Actions/Event/CreateEvent
vendor/bin/sail artisan make:class DataTransferObjects/Event/CreateEventDto
vendor/bin/sail artisan make:request StoreEventRequest
vendor/bin/sail artisan make:controller Event/EventController
vendor/bin/sail artisan make:resource Event/EventResource
vendor/bin/sail artisan make:policy EventPolicy --model=Event
vendor/bin/sail artisan make:event Event/EventCreated
vendor/bin/sail artisan make:listener Event/NotifyOnEventCreated
vendor/bin/sail artisan make:test EventTest --pest
vendor/bin/sail artisan make:test EventTest --pest --unit

# QA
composer qa           # rector → pint → phpstan → test
./sonar.sh            # SonarQube scan

# Desarrollo
composer dev          # server + queue + logs + vite
vendor/bin/sail artisan serve
vendor/bin/sail artisan queue:work
vendor/bin/sail artisan schedule:work

# Base de datos
vendor/bin/sail artisan migrate
vendor/bin/sail artisan migrate:fresh --seed
vendor/bin/sail artisan db:seed

# Cache
vendor/bin/sail artisan cache:clear
vendor/bin/sail artisan config:clear
vendor/bin/sail artisan view:clear
vendor/bin/sail artisan route:clear
```

---

*Fin del plan de implementacion.*
