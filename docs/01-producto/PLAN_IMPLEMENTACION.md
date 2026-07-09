# Plan de Implementacion: Plataforma de Eventos y Ticketing

**Proyecto:** eventos — Plataforma de eventos y ticketing
**Stack:** Laravel 12 / PHP 8.4 / MariaDB 11 / Redis / Livewire + Volt / Tailwind CSS 4
**Duracion estimada:** 24 semanas (6 fases de 4 semanas)
**Metodologia:** Sprints de 1 semana con entregables verificables por fase
**Referencia:** Hi.Events (funcional), Attendize (ticketing), Eventbrite (benchmark)

> **Estado de ejecucion (actualizacion post Sprint 3.4):** Sprints 1.1 al 1.4, Sprints 2.1 (Entradas), 2.2 (Checkout), 2.3 (Pagos con Stripe), 2.4 (Tickets PDF/QR), 3.1 (Check-in y Validación), 3.2 (Waitlist y Preguntas), 3.3 (Mensajes Masivos y Export) y 3.4 (Panel de Evento Completo) están **implementados, archivados y 100% verificados localmente**. Se cuenta con pasarela de cobros segura, generación de entradas con PDF y código QR únicos, control de reenvíos/concurrencia asíncrona, Magic Links seguros de un solo uso para asistentes, check-in operativo por cámara y lista manual, colas de lista de espera automáticas con transaccionalidad e idempotencia, recolección de información adicional (preguntas personalizadas) durante el checkout con validación en servidor, envíos masivos ad-hoc asíncronos con estrategia Outbox tolerante a fallos, exportación de asistentes a CSV en streaming nativo y un panel de evento completo con KPIs, settings y API operativa. El siguiente bloque planificado es el **Sprint T0 (Multitenancy Foundation)**, antes de iniciar Facturación.

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

**No es:** Un marketplace tipo Eventbrite con efecto red. Es una plataforma que cada organizador puede desplegar y operar de forma independiente.
**Decisión vigente:** arquitectura SaaS tenant-aware con una sola BBDD y `organizer_id` como scope; `Organizer.domain` se usa para branding/routing, no para separar datos por base de datos.

---

## 2. Principios de implementacion

### 2.1 Reglas de desarrollo

| Principio                         | Aplicacion                                                                 |
| --------------------------------- | -------------------------------------------------------------------------- |
| **SDD/TDD obligatorio**           | No se implementa codigo sin spec previa. Tests antes que codigo.           |
| **QA antes de commit**            | Rector → Pint → PHPStan → Tests → SonarQube. Nada falla, nada se commitea. |
| **Commits atomicos**              | Un commit = una unidad de trabajo. Feature, Fix o Chore.                   |
| **Acciones = casos de uso**       | Cada accion de negocio es una clase en `app/Actions/`.                     |
| **DTOs para transporte**          | FormRequest → toDto() → Controller → Action. Nunca `validated()` directo.  |
| **Models = aggregates practicos** | Eloquent models como aggregate roots. Logica de dominio en Actions.        |
| **Livewire = presentacion**       | Componentes Volt para UI interactiva. No logica de negocio en componentes. |
| **API REST = integracion**        | API versionada para mobile, webhooks, integraciones externas.              |

### 2.2 Convenciones del boilerplate

| Convencion    | Regla                                            |
| ------------- | ------------------------------------------------ |
| Tablas        | Singular (`event`, `order`, `product`)           |
| PK            | `{model}_id` (`event_id`, `order_id`)            |
| FK            | `{model}_id` (`organizer_id`, `event_id`)        |
| SoftDeletes   | Siempre en tablas nuevas                         |
| PHP           | `declare(strict_types=1)` en cada archivo        |
| Clases        | `final` por defecto                              |
| Idioma codigo | Ingles (clases, metodos, variables, migraciones) |
| Comentarios   | Espanol solo si son necesarios                   |
| Commits       | Ingles (conventional commits)                    |

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

| Fase                | Semanas | Sprints | Entregable principal                                    | Librerias nuevas                                           |
| ------------------- | ------- | ------- | ------------------------------------------------------- | ---------------------------------------------------------- |
| 1. Fundacion        | 1-4     | 4       | Auth, organizadores, eventos basicos, panel organizador | Sanctum, Permission, Activitylog, Purifier, Livewire, Volt |
| 2. Ticketing/Compra | 5-8     | 4       | Productos, checkout, Stripe, pedidos, tickets PDF       | Stripe SDK, Bacon QR, DomPDF                               |
| 3. Operacion        | 9-12    | 4       | Check-in, waitlist, preguntas, mensajes masivos, export | —                                                          |
| 4. Monetizacion     | 13-16   | 4       | Facturas, reembolsos, comisiones, payouts, reportes     | Horizon                                                    |
| 5. Discovery        | 17-20   | 4       | Catalogo publico, busqueda, SEO, widget, CDN            | Scout                                                      |
| 6. Admin/Pulido     | 21-24   | 4       | Backoffice, audit, GDPR, MFA, webhooks, deploy          | Deptrac (opcional)                                         |

---

## 4. Fase 1: Fundacion (Semanas 1-4)

**Objetivo:** Base tecnica funcional con autenticacion, gestion de organizadores y eventos, y panel de organizador basico.

### Sprint 1.1: Setup y Auth (Semana 1) — IMPLEMENTADO

**Spec:** Configurar el stack base y sistema de autenticacion.
**Estado:** Completado y archivado en OpenSpec (`openspec/changes/archive/2026-06-25-sprint-1-1-setup-auth`).

**Checks realizados:**

- [x] Auth flows verificados en navegador y tests
- [x] Roles globales y audit logging validados
- [x] QA/Sonar previos sin bloqueos críticos

| Tarea  | Detalle                             | Entregable                                                           |
| ------ | ----------------------------------- | -------------------------------------------------------------------- |
| 1.1.1  | Instalar librerias Fase 1           | `composer.json` actualizado                                          |
| 1.1.2  | Configurar Sanctum (cookie + token) | `config/sanctum.php`, `config/cors.php`                              |
| 1.1.3  | Configurar Spatie Permission        | Migraciones de roles/permisos                                        |
| 1.1.4  | Configurar Spatie Activitylog       | Migracion de `activity_log`                                          |
| 1.1.5  | Configurar mews/purifier            | `config/purifier.php` con perfiles                                   |
| 1.1.6  | Instalar Livewire + Volt            | `volt:install`, directorio `livewire/`                               |
| 1.1.7  | Crear modelo User con traits        | `User.php` con HasApiTokens, HasRoles, LogsActivity                  |
| 1.1.8  | Crear actions de auth               | `RegisterUser`, `LoginUser`, `RequestPasswordReset`, `VerifyEmail`   |
| 1.1.9  | Crear componentes Volt de auth      | `login.blade.php`, `register.blade.php`, `forgot-password.blade.php` |
| 1.1.10 | Crear layout base                   | `layouts/app.blade.php` con Tailwind                                 |
| 1.1.11 | Tests de auth                       | Tests de registro, login, reset, email verification                  |
| 1.1.12 | Tests de roles/permisos             | Tests de asignacion de roles, verificacion de permisos               |

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

| Area                     | Entregado real                                                                                                                                                                                                                                                                        |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Stack base               | Laravel 12 / PHP 8.4 / Sail; `composer.json` con `laravel/sanctum ^4.3`, `spatie/laravel-permission ^8.0`, `spatie/laravel-activitylog ^5.0`, `mews/purifier ^3.4`, `livewire/livewire ^4.3`, `livewire/volt ^1.10`                                                                   |
| Config publicada         | `config/sanctum.php`, `config/permission.php`, `config/activitylog.php`, `config/purifier.php`                                                                                                                                                                                        |
| Migraciones              | `personal_access_tokens_table`, `activity_log_table`, `permission_tables` (2026-06-23)                                                                                                                                                                                                |
| Modelo `User`            | `HasApiTokens`, `HasRoles`, `LogsActivity`, `Notifiable`; implementa `MustVerifyEmail`; activity log privacy-safe (`logOnly(['name','email'])`, `logOnlyDirty`, log name `user`)                                                                                                      |
| Actions de auth          | `app/Actions/Auth/`: `RegisterUserAction`, `LoginUserAction`, `LogoutUserAction`, `RequestPasswordResetAction`, `ResetPasswordAction`, `RecordAuthActivityAction`                                                                                                                     |
| Seeders                  | `DatabaseSeeder`, `RoleSeeder` (roles globales: `super_admin`, `platform_admin`, `attendee`; idempotente via `firstOrCreate`). Los roles de equipo del organizador (`admin`, `editor`, `viewer`) son catalogo de dominio en `App\Support\Organizers\OrganizerRoles`, no roles Spatie. |
| Componentes Volt         | `resources/views/livewire/auth/`: `login`, `register`, `forgot-password`, `reset-password`                                                                                                                                                                                            |
| Rutas (`routes/web.php`) | Volt routes `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` + POST controllers (`LoginController`, `RegisterController`, `LogoutController`, `RequestPasswordResetController`, `ResetPasswordController`) con throttle en login y reset request                  |
| Tests                    | `tests/Feature/Auth/` (Login, Logout, Register, PasswordReset, Throttling, AuthUi, AuthActionsGuardContract, AuthAuditSafety, PackageReadiness, UserReadiness), `tests/Feature/Audit/` (AuthAudit, AuthAuditFlow), `tests/Feature/Authorization/` (RoleMiddleware, RoleSeeder)        |
| QA / SonarQube           | `composer qa` (rector dry-run -> pint --dirty -> phpstan -> pest); `./sonar.sh`; coverage report path `build/logs/clover.xml` (`sonar-project.properties`)                                                                                                                            |

**Desviaciones respecto al plan (registradas):**

- Las Actions de auth usan sufijo `Action` (`RegisterUserAction`, etc.) en vez del nombre sin sufijo del plan. Convencion consistente en `app/Actions/Auth/`.
- Se anaden dos actions no previstas en la tabla: `LogoutUserAction` (logout explicito) y `ResetPasswordAction` (reset de password como accion propia), ademas de `RecordAuthActivityAction` para el audit logging de eventos de auth.
- La verificacion de email se delega al contrato `MustVerifyEmail` de Laravel + flow nativo en vez de una Action `VerifyEmail` dedicada.
- Sanctum funciona en modo cookie-based SPA (Livewire/Volt same-origin); el modo token-based queda disponible para la API futura. No se genero `config/cors.php` separado (Laravel 12 integra CORS en `bootstrap/app.php`).

---

### Sprint 1.2: Organizadores y Equipos (Semana 2) — IMPLEMENTADO

**Spec:** Sistema de organizadores con gestion de equipos.

**Estado:** Implementado, verificado y fusionado a `main`.

**Checks realizados:**

- [x] CRUD de organizer verificado (list/create/show/edit/delete)
- [x] Team management verificado (add/change/remove)
- [x] Policies y permisos de global admin verificados
- [x] Layout admin y formularios reutilizables validados
- [x] QA (`composer qa`) y Sonar verificados en su momento
- [x] Roles del organizer documentados como catálogo propio del dominio (`admin/editor/viewer`)

| Tarea  | Detalle                         | Entregable                                                                                                                        |
| ------ | ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| 1.2.1  | Migracion `organizer`           | Tabla con slug, branding, config                                                                                                  |
| 1.2.2  | Migracion `organizer_user`      | Tabla pivot con rol (admin, editor, viewer)                                                                                       |
| 1.2.3  | Modelo Organizer                | Con LogsActivity, relaciones                                                                                                      |
| 1.2.4  | Acciones de organizador         | `CreateOrganizer`, `UpdateOrganizer`, `AddTeamMember`, `RemoveTeamMember`                                                         |
| 1.2.5  | DTOs de organizador             | `CreateOrganizerDto`, `UpdateOrganizerDto`                                                                                        |
| 1.2.6  | FormRequests                    | `StoreOrganizerRequest`, `UpdateOrganizerRequest`                                                                                 |
| 1.2.7  | Componentes Volt de organizador | `organizer-dashboard`, `organizer-settings`, `team-management`                                                                    |
| 1.2.8  | Policies                        | `OrganizerPolicy`                                                                                                                 |
| 1.2.9  | Roles por organizador           | Catalogo de dominio `App\Support\Organizers\OrganizerRoles`: `admin`, `editor`, `viewer` (pivot `organizer_user.role`, no Spatie) |
| 1.2.10 | Tests de organizador            | CRUD, team management, policies                                                                                                   |

**Criterios de aceptacion:**

- [x] Usuario puede crear un organizador
- [x] Usuario puede invitar miembros al equipo con rol
- [x] Solo admin puede anadir/eliminar miembros (global admins habilitados por policy)
- [x] Dashboard de organizador muestra info basica
- [x] Settings de organizador (nombre, logo, color) editables
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 1.1 (User, Auth, Permission).

**Notas de implementacion:**

- Los roles del organizer (`admin`, `editor`, `viewer`) se modelan como catálogo propio del dominio, no como roles globales Spatie.
- El selector de usuarios del team no filtra por tenant/dominio en esta fase; el filtro de acceso lo resuelve la policy.
- La alta directa de usuarios asociados a organizer se difiere a Sprint 1.5.

---

### Sprint 1.3: Eventos Basicos (Semana 3) — IMPLEMENTADO

**Spec:** CRUD de eventos con configuracion basica.

**Estado:** Implementado con SDD en `openspec/changes/sprint-1-3-eventos-basicos`; `composer qa` pasa limpio localmente.

**Checks realizados:**

- [x] Migraciones, modelos, enums, factories y relaciones verificados
- [x] Actions/FormRequests/DTOs de eventos verificados
- [x] Policies y acceso cruzado entre organizers verificados
- [x] UI interna con filtros y acciones publish/pause/cancel verificada
- [x] `CategorySeeder` idempotente verificado
- [x] QA local (`composer qa`) limpio

**Objetivo operativo:** introducir el primer agregado de negocio (`Event`) sin mezclar todavia ticketing, checkout ni catalogo publico. El sprint debe dejar a un organizer creando, editando, publicando y cancelando eventos propios desde el panel interno.

**Estado recomendado antes de implementar:** abrir cambio SDD `sprint-1-3-eventos-basicos` con `proposal`, `spec`, `design`, `tasks`, implementacion y `verify/archive`.

**Alcance incluido:**

- Taxonomia basica de categorias.
- Venues reutilizables por organizer.
- Eventos con estado, visibilidad, fechas, descripcion sanitizada y relacion con organizer.
- Policies basadas en rol dentro del organizer (`admin`, `editor`, `viewer`) y acceso global admin.
- UI interna para listar, crear, editar, ver detalle, publicar y cancelar eventos.

**Fuera de alcance para este sprint:**

- Tipos de entrada, precios, cuotas, stock y checkout.
- Pagos, tickets, QR, asistentes y check-in.
- Catalogo publico SEO completo.
- Recurrencia avanzada de eventos.
- Integracion con mapas externos para venues.

**Decisiones de dominio para Sprint 1.3:**

| Tema         | Decision                                                                                                                                   |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `category`   | Taxonomia global simple con `parent_id` nullable. No pertenece a organizer en la primera version.                                          |
| `venue`      | Pertenece a un organizer. Un organizer puede reutilizar venues entre eventos.                                                              |
| `event`      | Pertenece siempre a un organizer y puede tener categoria y venue opcionales durante borrador.                                              |
| Publicacion  | `PublishEventAction` solo permite publicar si el evento tiene datos minimos: titulo, slug, fechas validas, venue, categoria y visibilidad. |
| Visibilidad  | `public`, `private`, `password_protected`. La visibilidad define descubrimiento futuro, no permisos internos del panel.                    |
| Sanitizacion | Descripciones HTML pasan por Purifier antes de persistirse.                                                                                |
| UI           | Usar el layout admin existente. No crear un segundo layout de organizer hasta Sprint 1.4.                                                  |

| Tarea  | Detalle                        | Entregable                                                                                              |
| ------ | ------------------------------ | ------------------------------------------------------------------------------------------------------- |
| 1.3.1  | Migracion `category`           | Tabla con nombre, slug, padre                                                                           |
| 1.3.2  | Migracion `venue`              | Tabla con direccion, ciudad, capacidad                                                                  |
| 1.3.3  | Migracion `event`              | Tabla con campos basicos, estado, visibilidad y relaciones                                              |
| 1.3.4  | Enum `EventStatus`             | draft, configured, published, paused, completed, cancelled                                              |
| 1.3.5  | Enum `EventVisibility`         | public, private, password_protected                                                                     |
| 1.3.6  | Modelos Category, Venue, Event | Con relaciones, casts, factories y activity logging en Event                                            |
| 1.3.7  | Acciones de evento             | `CreateEventAction`, `UpdateEventAction`, `PublishEventAction`, `PauseEventAction`, `CancelEventAction` |
| 1.3.8  | DTOs de evento                 | `CreateEventDto`, `UpdateEventDto`                                                                      |
| 1.3.9  | FormRequests                   | `CreateEventRequest`, `UpdateEventRequest`                                                              |
| 1.3.10 | UI Blade de evento             | Listado, formulario, detalle, filtros y acciones internas                                               |
| 1.3.11 | Policies                       | `EventPolicy`, `VenuePolicy`                                                                            |
| 1.3.12 | Tests de evento                | Migraciones, modelos, actions, requests, policies, UI                                                   |

#### Plan de implementacion TDD por PRs

| PR   | Corte                | Incluye                                                                                                                    | Pruebas minimas                                                                      |
| ---- | -------------------- | -------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| PR 1 | Modelo de datos      | Migraciones `category`, `venue`, `event`; enums; modelos; factories; relaciones; casts; activity logging                   | Tests de migraciones, relaciones, casts, factories y constraints basicas             |
| PR 2 | Casos de uso         | DTOs, FormRequests, Actions `CreateEventAction`, `UpdateEventAction`, `PublishEventAction`, `CancelEventAction`            | Tests de actions, validacion, transiciones de estado y sanitizacion                  |
| PR 3 | HTTP y autorizacion  | `EventController`, rutas nombradas, `EventPolicy`, Resources/ViewModels si hay multiples datos de lectura                  | Feature tests de permisos por rol, acceso cruzado entre organizers y respuestas HTTP |
| PR 4 | UI interna           | Listado, formulario, detalle, filtros basicos y acciones publish/cancel con Volt/Blade reutilizando componentes existentes | Tests de render, navegacion, formularios y estados visibles                          |
| PR 5 | Integracion y cierre | Ajustes de navegacion, docs, QA completo, Sonar, SDD verify/archive                                                        | `composer qa`, `./sonar.sh`, checklist de aceptacion completo                        |

#### Detalle esperado por capa

| Capa         | Implementacion esperada                                                                                                       |
| ------------ | ----------------------------------------------------------------------------------------------------------------------------- |
| Migrations   | Tablas singulares, PK `{model}_id`, FKs explicitas, soft deletes e indices por `organizer_id`, `slug`, `status`, `starts_at`. |
| Models       | `Category`, `Venue`, `Event` finales, con `fillable`, relaciones, `casts()` y `LogsActivity` donde aporte trazabilidad.       |
| Enums        | `EventStatus` y `EventVisibility` en `app/Enums/`, con valores string estables.                                               |
| DTOs         | `CreateEventDto` y `UpdateEventDto` sin logica; transporte desde FormRequest hacia Action.                                    |
| FormRequests | `StoreEventRequest` y `UpdateEventRequest` con `toDto()`. El controller no usa `validated()` directo.                         |
| Actions      | Una accion por caso de uso. No devuelven Response; retornan modelo o valor de dominio.                                        |
| Controllers  | Finos, sin logica de negocio. Usan Actions, Policies y Resources/ViewModels.                                                  |
| UI           | Volt/Blade solo coordina presentacion; no contiene reglas de publicacion ni mutaciones directas.                              |
| Tests        | Primero contratos de dominio y permisos; despues UI/HTTP. Cada bug o regla nueva requiere test.                               |

#### Reglas de estado

| Transicion                                         | Permitida si                                                                                                                         |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `draft` -> `configured`                            | El evento tiene campos minimos internos, pero aun no se publica.                                                                     |
| `draft/configured` -> `published`                  | Pasa `PublishEventAction` y el usuario tiene permiso de gestion del evento.                                                          |
| `published` -> `paused`                            | Se permite para detener venta/visibilidad futura sin cancelar el evento. Puede quedar preparado aunque la UI lo active mas adelante. |
| `draft/configured/published/paused` -> `cancelled` | Se permite con permiso de gestion. Debe registrar actividad.                                                                         |
| `published/paused` -> `completed`                  | No se automatiza en Sprint 1.3; queda para operacion/event lifecycle posterior.                                                      |

#### Checklist previo a implementacion

- [x] Crear proposal/spec/design/tasks SDD para `sprint-1-3-eventos-basicos`.
- [x] Confirmar si las categorias iniciales se cargan por seeder o se gestionan manualmente por admin global: se cargan por `CategorySeeder`.
- [x] Confirmar copy y campos minimos del formulario de evento.
- [x] Revisar componentes UI existentes antes de crear nuevos.
- [x] Definir filtros iniciales de listado: status, visibility, date range y text search simple.

**Criterios de aceptacion:**

- [x] Organizador puede crear evento borrador
- [x] Organizador puede editar evento (detalles, venue, categoria)
- [x] Organizador puede publicar evento (solo si esta configurado)
- [x] Organizador puede cancelar evento
- [x] Lista de eventos del organizador con filtros
- [x] Usuario con rol `viewer` puede ver eventos pero no mutarlos
- [x] Usuario de otro organizer no puede acceder al evento
- [x] Global admin puede auditar/gestionar eventos segun policy
- [x] Descripciones HTML se sanitizan antes de persistirse
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 1.2 (Organizer).

---

### Sprint 1.4: Panel de Organizador (Semana 4) — IMPLEMENTADO (CON AJUSTES DE CIERRE)

**Spec:** Dashboard del organizador con metricas basicas y navegacion.

| Tarea  | Detalle                  | Entregable                                                                           |
| ------ | ------------------------ | ------------------------------------------------------------------------------------ |
| 1.4.1  | Layout de organizador    | `layouts/organizer.blade.php` con sidebar                                            |
| 1.4.2  | Dashboard metricas       | Ventas totales, asistentes (placeholders), eventos activos, equipo (reales)          |
| 1.4.3  | Navegacion del panel     | Sidebar con enlaces a secciones                                                      |
| 1.4.4  | Detalle y KPIs de Evento | Detalle de evento integrado con navegación por pestañas en `show.blade.php`          |
| 1.4.5  | API routes basicas       | `/api/v1/organizers/{organizer}`, `/api/v1/organizers/{organizer}/events` (anidadas) |
| 1.4.6  | API Resources            | `OrganizerResource` (contrato restringido), `EventResource`                          |
| 1.4.7  | Middleware de contexto   | Reutilización de `organizer.detect` (multitenant)                                    |
| 1.4.8  | Tests de panel           | Dashboard, metricas, API, isolation y test negativo de Livewire                      |
| 1.4.9  | Documentacion API        | Integrada en las rutas api y recursos                                                |
| 1.4.10 | Retro de Fase 1          | Review de lo construido, ajustes                                                     |

**Criterios de aceptacion:**

- [x] Dashboard muestra metricas reales de negocio (Ventas/Asistentes mock/0, Eventos/Equipo dinámicos)
- [x] Navegacion funcional entre secciones
- [x] API REST basica funcional con auth Sanctum y diseño anidado
- [x] Middleware de contexto funciona (aislamiento del tenant verificado)
- [x] QA pipeline completo pasa limpio
- [x] Fase 1 completa: Auth + Organizer + Event + Panel

**Dependencias:** Sprints 1.1, 1.2, 1.3.

---

### Sprint 1.5: Onboarding de usuarios e invitaciones (Sprint posterior) — PENDIENTE

**Spec:** Flujo de alta de usuarios asociados a un organizer, con invitaciones y creación asistida de cuentas cuando tenga sentido de producto.

| Tarea | Detalle                                                  | Entregable                      |
| ----- | -------------------------------------------------------- | ------------------------------- |
| 1.5.1 | Definir si el alta es por invitación o creación asistida | Decision/Spec                   |
| 1.5.2 | Crear flujo de invitación de miembros                    | Invite flow                     |
| 1.5.3 | Crear alta asistida de usuario organizer (si aplica)     | Form/Action                     |
| 1.5.4 | Estados de invitación y aceptación                       | Pending/accepted                |
| 1.5.5 | Tests de onboarding                                      | Invitation and onboarding tests |

**Criterios de aceptacion:**

- [ ] El flujo de alta de usuarios queda definido como invitación o creación asistida.
- [ ] Se puede incorporar un miembro al organizer sin mezclar roles globales con roles del organizer.
- [ ] El flujo está cubierto por tests y QA.

**Dependencias:** Sprint 1.2 (Organizer), Sprint 1.4 (Panel de Organizador).

---

## 5. Fase 2: Ticketing y Compra (Semanas 5-8)

**Objetivo:** Venta de entradas con checkout funcional, pago por Stripe y generacion de tickets PDF con QR.

### Sprint 2.1: Productos y Tipos de Entrada (Semana 5)

| Tarea  | Detalle                                  | Entregable                                                               |
| ------ | ---------------------------------------- | ------------------------------------------------------------------------ |
| 2.1.1  | Migracion `product`                      | Tipos de entrada, add-ons, merch                                         |
| 2.1.2  | Migracion `product_price`                | Tiers de precio, quotas                                                  |
| 2.1.3  | Migracion `promo_code`                   | Codigos promocionales                                                    |
| 2.1.4  | Enum `ProductType`                       | ticket, addon, merchandise, donation                                     |
| 2.1.5  | Enum `PromoCodeType`                     | percentage, fixed                                                        |
| 2.1.6  | Modelos Product, ProductPrice, PromoCode | Con LogsActivity                                                         |
| 2.1.7  | Acciones de producto                     | `CreateProduct`, `UpdateProduct`, `SetProductPricing`, `CreatePromoCode` |
| 2.1.8  | Servicio `PriceCalculator`               | Calcula precios con taxes y descuentos                                   |
| 2.1.9  | Servicio `PromoCodeValidator`            | Valida aplicabilidad de promo codes                                      |
| 2.1.10 | Componentes Volt de producto             | `product-list`, `product-form`, `product-pricing`                        |
| 2.1.11 | Tests de producto                        | CRUD, pricing, promo codes                                               |

**Criterios de aceptacion:**

- [x] Organizador puede crear tipos de entrada con precios y quotas
- [x] Organizador puede configurar multiples tiers por producto
- [x] Organizador puede crear promo codes con reglas
- [x] PriceCalculator calcula correctamente subtotal, taxes, descuentos
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 1.3 (Event).

---

### Sprint 2.2: Ordenes y Checkout (Semana 6)

| Tarea  | Detalle                              | Entregable                                                                                                      |
| ------ | ------------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| 2.2.1  | Migracion `order`                    | Pedidos con estados y totales                                                                                   |
| 2.2.2  | Migracion `order_item`               | Lineas de pedido                                                                                                |
| 2.2.3  | Enum `OrderStatus`                   | pending, reserved, paid, confirmed, cancelled, expired, refunded                                                |
| 2.2.4  | Modelo Order, OrderItem              | Con LogsActivity, calculo de totales                                                                            |
| 2.2.5  | Servicio `StockManager`              | Reserva/libera stock atomicamente (Redis lock)                                                                  |
| 2.2.6  | Acciones de orden                    | `CreateOrder`, `ReserveStock`, `ApplyPromoCode`, `ProcessCheckout`, `CancelOrder`, `ReleaseExpiredReservations` |
| 2.2.7  | DTOs de orden                        | `CreateOrderDto`, `OrderItemDto`                                                                                |
| 2.2.8  | Command de liberacion                | `ReleaseExpiredReservations` via scheduler (cada minuto)                                                        |
| 2.2.9  | Componente Volt `checkout`           | Flujo de compra paso a paso                                                                                     |
| 2.2.10 | Componente Volt `order-confirmation` | Confirmacion post-compra                                                                                        |
| 2.2.11 | Tests de checkout                    | Creacion, reserva, expiracion, cancelacion                                                                      |
| 2.2.12 | Tests de concurrencia                | Overselling prevention                                                                                          |

**Criterios de aceptacion:**

- [x] Asistente puede seleccionar entradas y crear pedido
- [x] Stock se reserva con TTL de 10 minutos
- [x] Si expira, se libera automaticamente
- [x] Promo code se aplica correctamente
- [x] Tests de concurrencia pasan (no overselling)
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 2.1 (Product, Pricing).

---

### Sprint 2.3: Pagos con Stripe (Semana 7)

| Tarea  | Detalle                             | Entregable                                                |
| ------ | ----------------------------------- | --------------------------------------------------------- |
| 2.3.1  | Instalar Stripe SDK                 | `composer require stripe/stripe-php`                      |
| 2.3.2  | Migracion `payment`                 | Pagos con provider_id, status                             |
| 2.3.3  | Migracion `refund`                  | Reembolsos                                                |
| 2.3.4  | Enum `PaymentStatus`                | pending, completed, failed, refunded, partially_refunded  |
| 2.3.5  | Enum `PaymentMethod`                | stripe, paypal, offline                                   |
| 2.3.6  | Modelos Payment, Refund             | Con LogsActivity                                          |
| 2.3.7  | Interface `PaymentGatewayInterface` | Contrato para gateways                                    |
| 2.3.8  | Implementacion `StripeGateway`      | PaymentIntent, webhook, refund                            |
| 2.3.9  | Acciones de pago                    | `InitiatePayment`, `HandleStripeWebhook`, `ProcessRefund` |
| 2.3.10 | Endpoint webhook                    | `/api/v1/webhooks/stripe` con firma HMAC                  |
| 2.3.11 | Domain events                       | `PaymentCompleted`, `PaymentFailed`, `RefundProcessed`    |
| 2.3.12 | Listeners                           | `ConfirmOrderOnPaymentCompleted`, `NotifyOnPaymentFailed` |
| 2.3.13 | Tests de pago                       | PaymentIntent, webhook, refund                            |

**Criterios de aceptacion:**

- [x] Checkout redirige a Stripe Checkout o usa Elements
- [x] Webhook de Stripe confirma pago y actualiza orden
- [x] Orden pasa a `confirmed` tras pago exitoso
- [x] Reembolso funciona (total y parcial)
- [x] Webhook verificado con firma HMAC
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 2.2 (Order, Checkout).

---

### Sprint 2.4: Tickets PDF y QR (Semana 8) — IMPLEMENTADO

| Tarea  | Detalle                                      | Entregable                                                     |
| ------ | -------------------------------------------- | -------------------------------------------------------------- |
| 2.4.1  | Instalar Bacon QR + DomPDF                   | `composer require bacon/bacon-qr-code barryvdh/laravel-dompdf` |
| 2.4.2  | Migracion `attendee`                         | Asistentes con unique_code, status                             |
| 2.4.3  | Enum `AttendeeStatus`                        | active, cancelled, checked_in                                  |
| 2.4.4  | Modelo Attendee                              | Con LogsActivity, generacion de unique_code                    |
| 2.4.5  | Servicio `QrCodeGenerator`                   | Genera QR como PNG/SVG                                         |
| 2.4.6  | Servicio `TicketPdfGenerator`                | Genera PDF con branding del organizador                        |
| 2.4.7  | Accion `GenerateAttendeeQr`                  | Genera codigo QR para cada attendee                            |
| 2.4.8  | Listener `GenerateAttendeesOnOrderConfirmed` | Crea attendees tras confirmacion                               |
| 2.4.9  | Email de confirmacion                        | Template con PDF adjunto                                       |
| 2.4.10 | Componente Volt `my-orders`                  | Historial de compras del asistente                             |
| 2.4.11 | Tests de tickets                             | Generacion QR, PDF, email                                      |
| 2.4.12 | Retro de Fase 2                              | Review de lo construido, ajustes                               |

**Criterios de aceptacion:**

- [x] Tras pago confirmado, se generan attendees
- [x] Cada attendee tiene unique_code y QR
- [x] PDF de ticket se genera con branding del organizador
- [x] Email de confirmacion llega con PDF adjunto
- [x] Asistente ve sus pedidos y descarga tickets
- [x] QA pipeline pasa limpio
- [x] Fase 2 completa: Productos + Checkout + Stripe + Tickets

**Dependencias:** Sprints 2.1, 2.2, 2.3.

---

## 6. Fase 3: Operacion del Evento (Semanas 9-12)

**Objetivo:** Check-in, gestion de asistentes, herramientas operativas para el dia del evento.

### Sprint 3.1: Check-in y Validacion (Semana 9) — IMPLEMENTADO

| Tarea | Detalle                         | Entregable                                   |
| ----- | ------------------------------- | -------------------------------------------- |
| 3.1.1 | Migracion `check_in_list`       | Listas de check-in por evento                |
| 3.1.2 | Acciones de check-in            | `CheckInAttendeeAction`, `UndoCheckInAction` |
| 3.1.3 | Servicio de validacion QR       | Verifica unique_code, status, evento         |
| 3.1.4 | Componente Volt `check-in`      | Escaneo QR con camara (JS)                   |
| 3.1.5 | Componente Volt `attendee-list` | Lista de asistentes con busqueda             |
| 3.1.6 | Domain events                   | `AttendeeCheckedIn`, `CheckInUndone`         |
| 3.1.7 | Tests de check-in               | Validacion QR, undo, lista, policy           |

**Criterios de aceptacion:**

- [x] Escaner lee QR y valida entrada
- [x] QR usado se marca como checked_in
- [x] QR ya usado rechaza entrada
- [x] Undo check-in funciona
- [x] Lista de asistentes con busqueda por nombre/email
- [x] QA pipeline pasa limpio

**Notas de implementacion:**

- **Modelo de Datos de Acceso**: Desacoplamiento de tablas. Se creó `active_check_in` para llevar el control instantáneo del check-in y `check_in_log` para mantener un historial de auditoría inmutable (incluso al revertir entradas).
- **Control de Concurrencia**: `CheckInAttendeeAction` y `UndoCheckInAction` usan transacciones con bloqueos de base de datos (`lockForUpdate()`) para prevenir race conditions.
- **Autorización por Rol**: Implementadas habilidades explícitas (`viewCheckIn`, `checkIn`, `undoCheckIn`) en `EventPolicy` mapeando las capacidades de `admin`, `editor` y `viewer`.
- **UX Lector QR**: Lector basado en la cámara web ajustado para evitar distorsión de proporción y configurado con apagado de stream inmediato tras el escaneo para evitar bucles repetidos de lectura.
- **Sincronización Reactiva**: El escáner y la lista de asistentes se comunican asíncronamente mediante el evento global `check-in-updated` de Livewire.

**Dependencias:** Sprint 2.4 (Attendee).

---

### Sprint 3.2: Waitlist y Preguntas (Semana 10) — IMPLEMENTADO

| Tarea | Detalle                                   | Entregable                                               |
| ----- | ----------------------------------------- | -------------------------------------------------------- |
| 3.2.1 | Migracion `waitlist_entry`                | Lista de espera con posicion                             |
| 3.2.2 | Enum `WaitlistStatus`                     | waiting, notified, expired, converted                    |
| 3.2.3 | Modelo WaitlistEntry                      | Con LogsActivity                                         |
| 3.2.4 | Acciones de waitlist                      | `JoinWaitlist`, `NotifyWaitlist`, `ConvertWaitlistEntry` |
| 3.2.5 | Listener `NotifyWaitlistOnProductSoldOut` | Notifica al siguiente                                    |
| 3.2.6 | Campo `custom_questions` en event         | JSON con preguntas de registro                           |
| 3.2.7 | Campo `custom_answers` en attendee        | JSON con respuestas                                      |
| 3.2.8 | Componentes Volt de waitlist              | Formulario de union, gestion                             |
| 3.2.9 | Tests de waitlist                         | Union, notificacion, conversion                          |

**Criterios de aceptacion:**

- [x] Asistente puede unirse a waitlist si evento agotado
- [x] Cuando hay plaza, se notifica al siguiente
- [x] Preguntas personalizadas en checkout
- [x] Respuestas se almacenan en attendee
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 2.1 (Product), Sprint 2.4 (Attendee).

---

### Sprint 3.3: Mensajes Masivos y Export (Semana 11) — IMPLEMENTADO

| Tarea | Detalle                        | Entregable                                                       |
| ----- | ------------------------------ | ---------------------------------------------------------------- |
| 3.3.1 | Migracion `notification_log`   | Log de envios y tabla Outbox de destinatarios (completado)       |
| 3.3.2 | Acciones de notificacion       | `SendBulkMessage` (segmentado, flujo ad-hoc) (completado)        |
| 3.3.3 | Job `SendBulkEmailJob`         | Envio masivo via colas (con estrategia Outbox) (completado)      |
| 3.3.4 | Acciones de exportacion        | `ExportAttendeesAction` (CSV nativo con filtro de check-in real) |
| 3.3.5 | Componente Volt `bulk-message` | Formulario de mensaje masivo ad-hoc e historial (completado)     |
| 3.3.6 | Integracion en `attendee-list` | Boton de exportacion CSV con filtros (completado)                |
| 3.3.7 | Tests de mensajes              | Envio masivo ad-hoc HTML simple, encolado, export CSV (completado)|

> **Nota sobre alcance diferido:** La funcionalidad de plantillas reutilizables (`notification_template`) ha sido pospuesta para el **Sprint 3.4** (como parte de la configuración avanzada y settings de evento) o sprints posteriores, enfocando el Sprint 3.3 estrictamente en el flujo ad-hoc + registro de logs. El formato XLSX de exportación se pospone también para futuros sprints de reporte (Fase 4).
> La pestaña `Messages` reutiliza los componentes compartidos `x-form.input`, `x-form.select`, `x-form.textarea` y `x-ui.button`, y la validación vive en el componente Volt para no duplicar reglas en una `FormRequest` que Livewire no consume directamente.

**Criterios de aceptacion:**

- [x] Organizador puede enviar email masivo ad-hoc (HTML simple) a asistentes usando filtros segmentados (incluyendo estado de ingreso real contra `active_check_in`)
- [x] Mensajes se procesan via cola asíncrona (`completed_at` indica que los correos fueron encolados exitosamente en Redis/BD)
- [x] Export CSV nativo con filtros de busqueda, tipo de entrada y estado de check-in real
- [x] Log de envios e historial disponible en panel
- [x] QA pipeline pasa limpio

**Dependencias:** Sprint 3.1 (Attendee).

---

### Sprint 3.4: Panel de Evento Completo (Semana 12)

**Estado:** completado y verificado localmente.

| Tarea | Detalle                               | Entregable                                                   |
| ----- | ------------------------------------- | ------------------------------------------------------------ |
| 3.4.1 | Componente `event-dashboard` completo | KPIs, ventas en tiempo real, tareas                          |
| 3.4.2 | Componente `event-settings`           | Configuracion avanzada (SEO, emails, waitlist)               |
| 3.4.3 | Componente `sales-overview`           | Grafico de ventas diarias                                    |
| 3.4.4 | API routes de operacion               | `/api/v1/events/{event}/attendees`, `/check-in`, `/messages` |
| 3.4.5 | Tests de panel                        | Dashboard, settings, API                                     |
| 3.4.6 | Retro de Fase 3                       | Review de lo construido, ajustes                             |

**Criterios de aceptacion:**

- [x] Dashboard muestra ventas en tiempo real
- [x] Settings de evento configurables
- [x] API de operacion completa
- [x] QA pipeline pasa limpio
- [x] Fase 3 completa: Check-in + Waitlist + Mensajes + Panel

**Dependencias:** Sprints 3.1, 3.2, 3.3.

**Cierre formal:** archivado en `openspec/changes/archive/2026-07-07-sprint-3-4-panel-evento-completo/` con verify report y specs delta.

---

### Sprint T0: Multitenancy Foundation (pre-Fase 4)

**Objetivo:** dejar cerrada la base tenant-aware con `spatie/laravel-multitenancy` en single database antes de iniciar facturación.
**Regla de precedencia:** host del dominio raíz configurado por `APP_URL` > fallback por ruta interna `organizers/{organizer}` > contexto global sin tenant para superadmin.

| Tarea | Detalle | Entregable |
| ----- | ------- | ---------- |
| T0.1 | Integrar la librería y el `multitenancy.php` | Configuración base instalada |
| T0.2 | Resolver tenant por host con fallback compatible con rutas internas | Contexto tenant consistente |
| T0.3 | Hacer jobs/listeners tenant-aware y añadir tests de aislamiento | Runtime seguro para el resto de sprints |

**Criterios de aceptacion:**

- [ ] Tenant resuelto por el host del dominio raíz configurado por `APP_URL` cuando exista.
- [ ] Rutas internas por `organizers/{organizer}` siguen funcionando.
- [ ] Jobs/listeners restauran el tenant correcto.
- [ ] QA del sprint pasa limpio.

**Dependencias:** Sprints 1.1 al 3.4.

---

## 7. Fase 4: Monetizacion y Facturacion (Semanas 13-16)

**Objetivo:** Facturacion, reembolsos, comisiones de plataforma, payouts a organizadores, reportes avanzados.

### Sprint 4.1: Facturacion (Semana 13)

**Dependencia previa:** Sprint T0 (Multitenancy Foundation) para dejar cerrada la resolución tenant-aware y el soporte multi-dominio antes de tocar facturación.

**Ejecucion prevista:** mini-sprints secuenciales en `main` (`4.1a` base monetaria y esquema de factura, `4.1b` facturacion automatica, `4.1c` UX/reportes), con QA al cierre de cada bloque.

**Estado final:** Sprint 4.1 quedó completado, verificado y archivado en OpenSpec. Esta sección se conserva como referencia histórica.

### Sprint 4.1a: Base monetaria y esquema de factura (Semana 13) — COMPLETADO

**Objetivo:** dejar lista la base de facturación exacta antes de generar facturas automáticas o mostrar PDF/UX.

**Decisión cerrada:** la numeración de factura será **por organizador y año** (`organizer_id + year`) para evitar colisiones y facilitar auditoría.

| Tarea | Detalle | Entregable |
| ----- | ------- | ---------- |
| 4.1a.1 | Introducir precisión exacta para importes de facturación y reportes nuevos | Base sin `float` en el flujo nuevo |
| 4.1a.2 | Crear la persistencia de `invoice` con serie/número por organizador y año | Esquema de factura listo |
| 4.1a.3 | Añadir el almacenamiento mínimo de billing settings para evento y organizer | Metadatos operativos guardados |
| 4.1a.4 | Verificar la base con tests de modelo/request y QA focalizado | Foundation validada |

**Criterios de aceptacion:**

- [ ] Los nuevos importes de billing usan precisión exacta.
- [ ] La numeración de factura es estable por organizador y año.
- [ ] Billing settings mínimos se persisten sin tocar listeners ni PDF.
- [ ] QA del mini-sprint pasa limpio.

**Estado final:** ya ejecutado dentro de Sprint 4.1 y archivado.

| Tarea | Detalle                                    | Entregable                           |
| ----- | ------------------------------------------ | ------------------------------------ |
| 4.1.1 | Migracion `invoice`                        | Facturas con numero secuencial por organizador/año |
| 4.1.2 | Servicio `InvoicePdfGenerator`             | PDF de factura                       |
| 4.1.3 | Acciones de factura                        | `GenerateInvoice`, `IssueCreditNote` |
| 4.1.4 | Listener `GenerateInvoiceOnOrderConfirmed` | Auto-genera factura                  |
| 4.1.5 | Componente Volt `invoice-download`         | Descarga PDF                         |
| 4.1.6 | Tests de facturacion                       | Generacion, nota de credito, PDF     |

**Criterios de aceptacion:**

- [ ] Factura se genera automaticamente tras pago
- [ ] Numero secuencial e inmutable por organizador/año
- [ ] PDF descargable
- [ ] Nota de credito en reembolso
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 2.3 (Payment), Sprint 2.4 (Attendee).

---

### Sprint 4.2: Comisiones y Payouts (Semana 14) — PLANIFICADO

**Objetivo:** registrar comisiones y payouts internos sin mover dinero real todavía.

**Alcance funcional:**

- cálculo interno de comisiones usando los importes exactos de Sprint 4.1;
- registro de payouts por organizer con estados operativos;
- ajustes por refund para mantener trazabilidad;
- reportes filtrables con export CSV;
- settings con simulación de comisión y ayudas contextuales.

**Fuera de alcance:**

- Stripe Connect real;
- onboarding/KYC;
- transferencias automáticas de dinero;
- contabilidad legal completa.

**Estrategia de implementación:**

| Slice | Objetivo | Resultado esperado |
|---|---|---|
| 4.2a | Base de comisiones y payout records | Modelo `payout`, estado interno, `CommissionCalculator`, tests unitarios. |
| 4.2b | Flujo operativo y ajustes | Actions/listeners para crear y ajustar payouts cuando haya pagos o refunds. |
| 4.2c | UX y reportes | Settings de comisión, vista de payouts con filtros y export CSV. |

**Dependencias:** Sprint 4.1 (Invoice), Sprint 2.3 (Stripe).

**Criterios de aceptación:**

- [ ] La comisión se calcula con precisión exacta.
- [ ] Cada payout queda trazado por organizer y estado.
- [ ] Los refunds ajustan o revierten el payout afectado.
- [ ] La UI permite simular y consultar la comisión.
- [ ] Los reportes se pueden filtrar y exportar.

---

### Sprint 4.3: Reportes Avanzados (Semana 15)

| Tarea | Detalle                        | Entregable                            |
| ----- | ------------------------------ | ------------------------------------- |
| 4.3.1 | Servicio `ReportGenerator`     | Genera reportes de ventas, asistentes |
| 4.3.2 | Componente `sales-report`      | Informe de ventas por periodo         |
| 4.3.3 | Componente `attendee-report`   | Informe de asistentes                 |
| 4.3.4 | Componente `dashboard-metrics` | Metricas avanzadas                    |
| 4.3.5 | Export de reportes             | PDF, CSV, XLSX                        |
| 4.3.6 | Tests de reportes              | Generacion, export                    |

**Criterios de aceptacion:**

- [ ] Reporte de ventas por dia/semana/mes
- [ ] Reporte de asistentes con filtros
- [ ] Metricas de dashboard actualizadas
- [ ] Export funciona
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 4.1 (Invoice), Sprint 4.2 (Payout).

---

### Sprint 4.4: Retro y Ajustes (Semana 16)

| Tarea | Detalle                  | Entregable              |
| ----- | ------------------------ | ----------------------- |
| 4.4.1 | Instalar Laravel Horizon | Monitor de colas Redis  |
| 4.4.2 | Optimizacion de colas    | Priorizacion de jobs    |
| 4.4.3 | Retro de Fase 4          | Review de lo construido |
| 4.4.4 | Ajustes y fixes          | Issues pendientes       |

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

| Tarea | Detalle                              | Entregable                  |
| ----- | ------------------------------------ | --------------------------- |
| 5.1.1 | Layout publico                       | `layouts/public.blade.php`  |
| 5.1.2 | Componente `event-list-public`       | Listado publico con filtros |
| 5.1.3 | Componente `event-detail-public`     | Pagina publica del evento   |
| 5.1.4 | Componente `event-card`              | Card reutilizable           |
| 5.1.5 | Filtros por categoria, ciudad, fecha | Busqueda basica             |
| 5.1.6 | Tests de catalogo                    | Listado, detalle, filtros   |

**Criterios de aceptacion:**

- [ ] Pagina publica lista eventos con filtros
- [ ] Detalle de evento visible sin login
- [ ] Compra desde pagina publica
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.3 (Event), Sprint 2.4 (Attendee).

---

### Sprint 5.2: Busqueda con Meilisearch (Semana 18)

| Tarea | Detalle                       | Entregable                       |
| ----- | ----------------------------- | -------------------------------- |
| 5.2.1 | Instalar Laravel Scout        | `composer require laravel/scout` |
| 5.2.2 | Configurar Meilisearch driver | `config/scout.php`               |
| 5.2.3 | Modelo Event con Searchable   | Indexacion automatica            |
| 5.2.4 | Busqueda full-text            | Texto, categoria, ciudad, fecha  |
| 5.2.5 | Facets y filtros              | Meilisearch facets               |
| 5.2.6 | Tests de busqueda             | Indexacion, busqueda, facets     |

**Criterios de aceptacion:**

- [ ] Busqueda full-text funcional
- [ ] Filtros por categoria, ciudad, fecha
- [ ] Resultados ordenados por relevancia/fecha
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 5.1 (Catalogo).

---

### Sprint 5.3: SEO y Widget (Semana 19)

| Tarea | Detalle                | Entregable                      |
| ----- | ---------------------- | ------------------------------- |
| 5.3.1 | SEO meta tags          | Title, description, OG, Twitter |
| 5.3.2 | Sitemap XML            | Generacion automatica           |
| 5.3.3 | URLs amigables         | Slugs por evento                |
| 5.3.4 | Widget embebible       | JS snippet para webs externas   |
| 5.3.5 | API publica del widget | `/api/v1/events/{slug}/widget`  |
| 5.3.6 | Tests de SEO           | Meta tags, sitemap, widget      |

**Criterios de aceptacion:**

- [ ] Meta tags correctos en cada pagina
- [ ] Sitemap.xml generado
- [ ] URLs amigables con slug
- [ ] Widget embebible funcional
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 5.2 (Busqueda).

---

### Sprint 5.4: Optimizacion de Rendimiento (Semana 20)

| Tarea | Detalle                 | Entregable                   |
| ----- | ----------------------- | ---------------------------- |
| 5.4.1 | Cache Redis             | Cache de lecturas frecuentes |
| 5.4.2 | Optimizacion de queries | Indexes, eager loading       |
| 5.4.3 | CDN para assets         | MinIO como S3-compatible     |
| 5.4.4 | Paginacion optimizada   | Cursor pagination            |
| 5.4.5 | Health checks           | `/up`, `/health`             |
| 5.4.6 | Tests de rendimiento    | Load testing basico          |
| 5.4.7 | Retro de Fase 5         | Review de lo construido      |

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

| Tarea | Detalle                        | Entregable                 |
| ----- | ------------------------------ | -------------------------- |
| 6.1.1 | Layout de admin                | `layouts/admin.blade.php`  |
| 6.1.2 | Componente `admin-dashboard`   | Metricas globales          |
| 6.1.3 | Componente `user-management`   | Gestion de usuarios        |
| 6.1.4 | Componente `moderate-events`   | Moderacion de eventos      |
| 6.1.5 | Componente `platform-settings` | Configuracion global       |
| 6.1.6 | API routes de admin            | `/api/v1/admin/*`          |
| 6.1.7 | Tests de admin                 | CRUD, moderacion, settings |

**Criterios de aceptacion:**

- [ ] Admin puede gestionar usuarios
- [ ] Admin puede moderar eventos
- [ ] Configuracion de comisiones global
- [ ] Metricas globales visibles
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 1.1 (User, Permission).

---

### Sprint 6.2: Audit, GDPR y MFA (Semana 22)

| Tarea | Detalle                    | Entregable                             |
| ----- | -------------------------- | -------------------------------------- |
| 6.2.1 | Panel de actividad         | Visualizacion de `activity_log`        |
| 6.2.2 | Export de datos personales | GDPR: descargar datos del usuario      |
| 6.2.3 | Derecho al olvido          | GDPR: anonymize + soft delete          |
| 6.2.4 | MFA TOTP                   | RFC 6238, QR setup                     |
| 6.2.5 | Acciones de MFA            | `EnableMfa`, `DisableMfa`, `VerifyMfa` |
| 6.2.6 | Tests de GDPR              | Export, delete, anonymize              |
| 6.2.7 | Tests de MFA               | Setup, verify, disable                 |

**Criterios de aceptacion:**

- [ ] Audit log visible en admin
- [ ] Usuario puede exportar sus datos
- [ ] Usuario puede solicitar eliminacion
- [ ] MFA funcional para organizadores
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 6.1 (Admin), Sprint 1.1 (User).

---

### Sprint 6.3: Webhooks Outbound y Documentacion (Semana 23)

| Tarea | Detalle                      | Entregable                         |
| ----- | ---------------------------- | ---------------------------------- |
| 6.3.1 | Migracion `webhook`          | Webhooks por organizador           |
| 6.3.2 | Migracion `webhook_delivery` | Log de entregas                    |
| 6.3.3 | Servicio `WebhookDispatcher` | Envio de webhooks firmados         |
| 6.3.4 | Configuracion de webhooks    | UI para gestionar webhooks         |
| 6.3.5 | Documentacion API completa   | OpenAPI/Swagger                    |
| 6.3.6 | Documentacion de desarrollo  | README, CONTRIBUTING, arquitectura |
| 6.3.7 | Tests de webhooks            | Envio, firma, reintento            |

**Criterios de aceptacion:**

- [ ] Organizador puede configurar webhooks
- [ ] Webhooks se envian firmados con HMAC
- [ ] Reintentos con backoff exponencial
- [ ] Documentacion API completa
- [ ] QA pipeline pasa limpio

**Dependencias:** Sprint 6.1 (Admin).

---

### Sprint 6.4: Deploy y Cierre (Semana 24)

| Tarea | Detalle                     | Entregable                          |
| ----- | --------------------------- | ----------------------------------- |
| 6.4.1 | Configuracion de produccion | `.env.production`, variables        |
| 6.4.2 | CI/CD pipeline              | GitHub Actions: test, build, deploy |
| 6.4.3 | Backups automaticos         | Database + storage                  |
| 6.4.4 | Monitoring                  | Sentry configurado                  |
| 6.4.5 | Load testing                | Pruebas de carga                    |
| 6.4.6 | Documentacion final         | Manual de usuario, admin, deploy    |
| 6.4.7 | Retro final                 | Review de todo el proyecto          |

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

| Fase   | Bloqueada por | Razon                                           |
| ------ | ------------- | ----------------------------------------------- |
| Fase 2 | Fase 1        | Necesita Event + Organizer para crear productos |
| Fase 3 | Fase 2        | Necesita Attendee + Order para check-in         |
| Fase 4 | Fase 2        | Necesita Payment + Order para facturacion       |
| Fase 5 | Fase 1        | Necesita Event para catalogo publico            |
| Fase 6 | Fase 1        | Necesita User + Permission para admin           |

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

| Metrica         | Umbral                                 |
| --------------- | -------------------------------------- |
| PHPStan level   | 8 (sin errores)                        |
| Coverage minimo | 80% (nuevos archivos)                  |
| SonarQube       | Sin bugs criticos, sin vulnerabilities |
| Tests           | Todos en verde                         |
| Rector          | Sin cambios pendientes                 |
| Pint            | Sin cambios de formato                 |

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

| Riesgo                                         | Probabilidad | Impacto | Mitigacion                                             | Fase  |
| ---------------------------------------------- | ------------ | ------- | ------------------------------------------------------ | ----- |
| Overselling (vender mas que capacidad)         | Alta         | Critico | Redis lock + SELECT FOR UPDATE + tests de concurrencia | 2     |
| Webhooks perdidos (Stripe)                     | Media        | Alto    | Reintentos con backoff + reconciliacion periodica      | 2     |
| Reservas fantasma (stock no liberado)          | Media        | Alto    | TTL estricto + scheduler cada minuto + monitor         | 2     |
| HTMLPurifier deprecations PHP 8.5+             | Media        | Medio   | Monitorizar releases. Plan de migracion a custom.      | 1     |
| Livewire v4 breaking changes                   | Baja         | Medio   | Pin version. Leer changelog antes de actualizar.       | 1     |
| Scope creep (anadir funcionalidad sin control) | Alta         | Alto    | Roadmap por fases. MVP claro. Backlog priorizado.      | Todas |
| Complejidad DDD prematura                      | Media        | Medio   | Solo bounded contexts necesarios por fase.             | 1-3   |
| Rendimiento en venta masiva                    | Media        | Alto    | Redis queue, CDN, cache, paginacion, indexes.          | 5     |
| GDPR compliance                                | Media        | Alto    | Export de datos, derecho al olvido, consentimiento.    | 6     |
| Fraude en pagos                                | Media        | Alto    | Stripe Radar, rate limiting, monitor de anomalias.     | 2     |

---

## 13. Metricas de progreso

### Seguimiento por fase

| Fase                | Semanas | Sprints | Tareas totales | Completadas      | %       |
| ------------------- | ------- | ------- | -------------- | ---------------- | ------- |
| 1. Fundacion        | 1-4     | 4       | ~50            | ~12 (Sprint 1.1) | ~24%    |
| 2. Ticketing/Compra | 5-8     | 4       | ~50            | 0                | 0%      |
| 3. Operacion        | 9-12    | 4       | ~35            | 0                | 0%      |
| 4. Monetizacion     | 13-16   | 4       | ~30            | 0                | 0%      |
| 5. Discovery        | 17-20   | 4       | ~30            | 0                | 0%      |
| 6. Admin/Pulido     | 21-24   | 4       | ~35            | 0                | 0%      |
| **Total**           | **24**  | **24**  | **~230**       | **~12**          | **~5%** |

### Indicadores de salud del proyecto

| Indicador              | Objetivo              | Actual                                                           |
| ---------------------- | --------------------- | ---------------------------------------------------------------- |
| Tests passing          | 100%                  | Verde (Sprint 1.1)                                               |
| Coverage               | >80%                  | En umbral Sprint 1.1; coverage report en `build/logs/clover.xml` |
| PHPStan level          | 8                     | Limpio (Sprint 1.1)                                              |
| SonarQube quality gate | Pass                  | Sin errores criticos (Sprint 1.1)                                |
| Technical debt ratio   | <5%                   | —                                                                |
| Bugs abiertos          | 0 al cierre de sprint | 0 (Sprint 1.1 cerrado)                                           |

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

## Apendice C: Checklist de despliegue para migraciones

Usar este checklist antes de desplegar migraciones a produccion, especialmente para migraciones con logica de transformacion de datos (como la migracion de roles de organizizador `2026_06_27_000001_change_organizer_user_role_id_to_role_string.php`).

### Antes de migrar

- [ ] **Backup de base de datos** completo antes de cualquier migracion destructiva.
- [ ] **Verificar datos limpios**: ejecutar queries de validacion para confirmar que no hay datos sucios o huérfanos que puedan causar fallos en el preflight.
    ```sql
    -- Ejemplo para migracion de roles: verificar que todos los role_id existen en roles
    SELECT COUNT(*) FROM organizer_user ou
    LEFT JOIN roles r ON ou.role_id = r.id
    WHERE r.id IS NULL AND ou.role_id IS NOT NULL;
    ```
- [ ] **Orden de migraciones**: confirmar que las migraciones dependientes se ejecutan en el orden correcto (verificar timestamps y dependencias de tablas/columnas).
- [ ] **Rollback probado en staging**: ejecutar `migrate:rollback` en un entorno de staging con datos reales antes de produccion.
- [ ] **Seeders actualizados**: si la migracion depende de datos seedeados (ej. roles legacy), confirmar que el seeder los recrea o que la migracion los crea internamente.

### Durante la migracion

- [ ] **Ejecutar en ventana de mantenimiento** si la migracion es destructiva o bloquea tablas.
- [ ] **Monitorear logs** para detectar errores de preflight o timeouts.
- [ ] **No interrumpir** el proceso una vez iniciado (las migraciones son transaccionales por defecto en Laravel, pero operaciones DDL pueden no serlo en MySQL/MariaDB).

### Despues de migrar

- [ ] **Verificar schema**: confirmar que las columnas/indices esperados existen y los obsoletos fueron eliminados.
    ```bash
    vendor/bin/sail artisan db:show --counts
    ```
- [ ] **Verificar datos**: ejecutar queries de validacion post-migracion para confirmar que los datos fueron transformados correctamente.
    ```sql
    -- Ejemplo post-migracion de roles: verificar que todos los roles fueron migrados
    SELECT role, COUNT(*) FROM organizer_user GROUP BY role;
    ```
- [ ] **Ejecutar tests**: correr el suite de tests para confirmar que la aplicacion funciona correctamente con el nuevo schema.
    ```bash
    vendor/bin/sail composer qa
    ```
- [ ] **Limpiar cache** si el schema cambio y hay consultas cacheadas.

### Rollback (si es necesario)

- [ ] **Evaluar fix-forward vs rollback**: si la migracion fallo parcialmente, considerar si es mas seguro avanzar con una migracion correctiva que hacer rollback.
- [ ] **Backup antes de rollback**: incluso si ya hay backup pre-migracion, hacer un backup adicional antes de rollback.
- [ ] **Probar rollback en staging** con los datos actuales de produccion (restaurados en staging).
- [ ] **Verificar que rollback es determinista**: la migracion debe recrear datos dependientes (ej. roles legacy) si fueron eliminados.
- [ ] **Ejecutar rollback**:
    ```bash
    vendor/bin/sail artisan migrate:rollback --path=database/migrations/2026_06_27_000001_change_organizer_user_role_id_to_role_string.php
    ```
- [ ] **Verificar schema restaurado** y datos intactos post-rollback.

### Notas especificas para la migracion de roles de organizizador

- **Preflight check**: la migracion valida que todos los `role_id` (en `up()`) o `role` (en `down()`) sean mapeables antes de cualquier cambio de schema. Si hay datos sucios, lanza `RuntimeException` sin mutar el schema ni la tabla `roles`.
- **Roles legacy**: el `down()` recrea los roles `admin`, `editor`, `viewer` en la tabla `roles` si no existen, ya que el `RoleSeeder` actual ya no los seedea (ver A13). Solo se ejecuta si la validacion de datos pasa.
- **Orden**: esta migracion debe ejecutarse despues de `create_organizer_user_table` y antes de cualquier migracion que dependa de la columna `role` string.

### Plan de despliegue no-rolling para migraciones destructivas

Esta migracion es destructiva (elimina la columna `role_id` y la reemplaza por `role` string). **NO debe desplegarse con estrategia rolling** porque distintas instancias de la aplicacion verian schemas incompatibles durante el despliegue.

#### Checklist de despliegue en modo mantenimiento (single-step)

- [ ] **1. Backup completo** de la base de datos antes de cualquier operacion.
    ```bash
    vendor/bin/sail artisan db:show --counts  # documentar estado pre-migracion
    mysqldump -u root -p eventos > backup_pre_migration.sql
    ```
- [ ] **2. Activar modo mantenimiento**.
    ```bash
    vendor/bin/sail artisan down --retry=60 --refresh=5
    ```
- [ ] **3. Desplegar codigo nuevo** (una sola vez, sin rolling).
    ```bash
    git pull origin main
    composer install --no-dev --optimize-autoloader
    ```
- [ ] **4. Ejecutar migracion**.
    ```bash
    vendor/bin/sail artisan migrate --force
    ```
- [ ] **5. Verificar schema y datos**.
    ```bash
    vendor/bin/sail artisan db:show --counts
    # SQL de verificacion:
    # SELECT role, COUNT(*) FROM organizer_user GROUP BY role;
    # SELECT COUNT(*) FROM organizer_user WHERE role IS NULL;  -- debe ser 0
    ```
- [ ] **6. Ejecutar comando de verificacion legacy** (si existe) para validar consistencia post-migracion.
    ```bash
    vendor/bin/sail artisan organizers:verify-legacy-roles  # si aplica
    ```
- [ ] **7. Desactivar modo mantenimiento**.
    ```bash
    vendor/bin/sail artisan up
    ```
- [ ] **8. Ejecutar QA suite en entorno local/dev** para confirmar que la aplicacion funciona con el nuevo schema. En produccion con `--no-dev`, usar las verificaciones manuales y logs porque las herramientas QA pueden no estar instaladas.
    ```bash
    vendor/bin/sail composer qa
    ```

#### Alternativa: Expand/Contract (para futuras migraciones destructivas)

Para migraciones destructivas en produccion con multiples instancias, considerar el patron expand/contract:

1. **Expand**: anadir la nueva columna `role` sin eliminar `role_id`. Escribir en ambas.
2. **Migrate**: backfill de datos de `role_id` a `role`.
3. **Contract**: eliminar `role_id` una vez que todas las instancias usan `role`.

Este patron permite despliegue rolling pero requiere mas ciclos de desarrollo. Para este proyecto (solo/local), el modo mantenimiento es suficiente.

#### SQL de deteccion de datos no mapeables antes de `up()`

Ejecutar ANTES de la migracion `up()` para detectar problemas en el schema legacy (`role_id` existe, `role` aun no existe):

```sql
-- 1. Detectar role_id huérfanos (no existen en tabla roles)
SELECT COUNT(*) AS orphan_role_ids
FROM organizer_user ou
LEFT JOIN roles r ON ou.role_id = r.id
WHERE r.id IS NULL AND ou.role_id IS NOT NULL;

-- 2. Detectar role_id que existen pero no son mapeables (no son admin/editor/viewer)
SELECT COUNT(*) AS unmapped_role_ids
FROM organizer_user ou
INNER JOIN roles r ON ou.role_id = r.id
WHERE r.name NOT IN ('admin', 'editor', 'viewer');

-- 3. Detectar role_id NULL inesperados
SELECT COUNT(*) AS null_role_ids
FROM organizer_user
WHERE role_id IS NULL;
```

Si cualquiera de estas queries retorna un valor > 0, **NO ejecutar la migracion `up()`**. Limpiar los datos primero.

#### SQL de deteccion antes de rollback / `down()`

Ejecutar solo si la migracion `up()` ya se aplico (`role` existe, `role_id` ya no existe):

```sql
-- 1. Detectar role strings no mapeables
SELECT COUNT(*) AS unmapped_role_strings
FROM organizer_user
WHERE role NOT IN ('admin', 'editor', 'viewer') AND role IS NOT NULL;

-- 2. Detectar valores NULL inesperados
SELECT COUNT(*) AS null_roles
FROM organizer_user
WHERE role IS NULL;
```

Si cualquiera de estas queries retorna un valor > 0, **NO ejecutar rollback**. Aplicar fix-forward o limpiar datos primero.

#### Monitoreo y triggers de rollback (proyecto solo/local)

Para un proyecto local o de un solo desarrollador, no se requiere Sentry ni herramientas complejas de monitoreo. Usar:

- **Logs de Laravel**: revisar `storage/logs/laravel.log` despues de la migracion.
    ```bash
    tail -100 storage/logs/laravel.log | grep -i "error\|exception\|migration"
    ```
- **QA suite**: ejecutar `composer qa` despues de la migracion para confirmar que todos los tests pasan.
- **Verificacion manual**: navegar las vistas de organizador y confirmar que los roles se muestran correctamente.

**Triggers para fix-forward (avanzar con correccion):**

- La migracion fallo pero el schema esta parcialmente mutado (ej. columna `role` creada pero `role_id` no eliminado).
- Los datos migrados son incorrectos (ej. roles NULL o valores inesperados).
- Los tests fallan despues de la migracion.

**Triggers para rollback:**

- La migracion fallo completamente y el schema no fue mutado (safe to rollback).
- Se detectan datos corruptos post-migracion que no pueden corregirse con una migracion correctiva.

**Procedimiento de rollback:**

```bash
# 1. Activar modo mantenimiento
vendor/bin/sail artisan down

# 2. Rollback de la migracion
vendor/bin/sail artisan migrate:rollback --path=database/migrations/2026_06_27_000001_change_organizer_user_role_id_to_role_string.php --force

# 3. Verificar schema restaurado
vendor/bin/sail artisan db:show --counts

# 4. Restaurar codigo anterior si es necesario
git checkout HEAD~1 -- .

# 5. Desactivar modo mantenimiento
vendor/bin/sail artisan up
```

---

_Fin del plan de implementacion._
