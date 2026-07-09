# Valoracion tecnica e integracion de librerias seleccionadas

**Proyecto:** eventos — Plataforma de eventos y ticketing
**Fecha:** 22/06/2026
**Stack base:** Laravel 12 / PHP 8.4 / MariaDB 11 / Redis / Sail

---

## Resumen ejecutivo

Se han evaluado 10 librerias para integrar en el proyecto. De las 10, **9 son aprobadas sin reservas** y **1 requiere decision informada** (HTMLPurifier). A continuacion, la valoracion individual y la estrategia de integracion.

> **Estado de integracion (post Sprint 3.1):** Las 6 librerias de Fase 1 estan **instaladas y operativas** en el repositorio (`composer.json`): `laravel/sanctum ^4.3`, `spatie/laravel-permission ^8.0`, `spatie/laravel-activitylog ^5.0`, `mews/purifier ^3.4`, `livewire/livewire ^4.3`, `livewire/volt ^1.10`. Adicionalmente, para la generación y validación de entradas (Sprint 2.4 y 3.1), se han integrado `bacon/bacon-qr-code ^2.0`, `barryvdh/laravel-dompdf ^3.0` y `html5-qrcode` (JS CDN). `spatie/laravel-multitenancy` sigue fuera de Fase 1-3 y se activará en Sprint T0 con el host raíz del entorno definido por `APP_URL`.

---

## 1. Tabla de compatibilidad

| Libreria | Version | PHP req | Laravel req | L12 + PHP 8.4 | Installs | Open Issues | Veredicto |
|---|---|---|---|---|---|---|---|
| `laravel/sanctum` | v4.3.2 | ^8.2 | ^11\|^12\|^13 | Compatible | 191M | 3 | APROBADO |
| `ezyang/htmlpurifier` | v4.19.0 | ~5.6-8.5 | N/A | Compatible con advertencias | 349M | 133 | VER NOTA |
| `livewire/livewire` | v4.3.1 | ^8.1 | ^10\|^11\|^12\|^13 | Compatible | 87M | 33 | APROBADO |
| `livewire/volt` | v1.10.5 | ^8.1 | ^10.38\|^11\|^12\|^13 | Compatible | 6.3M | 12 | APROBADO |
| `spatie/laravel-activitylog` | v5.0.0 | ^8.4 | ^12\|^13 | Compatible | 52M | 3 | APROBADO |
| `spatie/laravel-multitenancy` | v4.1.3 | ^8.2 | ^11\|^12\|^13 | Compatible | 3.3M | 0 | APROBADO |
| `spatie/laravel-permission` | v8.0.0 | ^8.3 | ^12\|^13 | Compatible | 101M | 2 | APROBADO |
| `bacon/bacon-qr-code` | v2.0 | ^7.1\|^8.0 | N/A | Compatible | 20M | 5 | APROBADO |
| `barryvdh/laravel-dompdf` | v3.0 | ^8.0 | ^10\|^11\|^12 | Compatible | 38M | 15 | APROBADO |
| `html5-qrcode` (JS) | v2.3.8 | N/A | N/A | Compatible | N/A | N/A | APROBADO |

---

## 2. Valoracion individual

### 2.1 laravel/sanctum v4.3.2

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Autenticacion para SPAs (cookie-based) y APIs simples (token-based) |
| Por que encaja | El boilerplate no tiene auth instalada. Sanctum es la solucion oficial de Laravel para este caso. |
| Riesgo | Minimo. Es maintained por el equipo core de Laravel. |
| Alternativas descartadas | Passport (overkill para este caso), JWT puro (mas complejo sin beneficio claro) |

**Integracion en el proyecto:**

```
Auth flow:
  SPA (Livewire) → Sanctum cookie-based (same-origin)
  API (mobile/futuro) → Sanctum token-based (Bearer token)

Archivos afectados:
  - config/sanctum.php (nuevo, tras install)
  - config/cors.php (nuevo, tras install)
  - config/session.php (driver → database o redis)
  - bootstrap/app.php (middleware stateful para API)
  - app/Models/User.php (añadir HasApiTokens trait)
  - database/migrations/ (tabla personal_access_tokens)
```

**Fase de instalacion:** Fase 1 (Fundacion).

---

### 2.2 ezyang/htmlpurifier v4.19.0

**Veredicto: APROBADO CON ADVERTENCIAS. Se recomienda usar `mews/purifier` como wrapper.**

| Aspecto | Detalle |
|---|---|
| Que hace | Filtra HTML malicioso (XSS) usando whitelists estrictas. Convierte HTML no conforme a estandares. |
| Por que encaja | Los organizadores escriben descripciones de eventos con formato rico (WYSIWYG). Necesitamos almacenar HTML seguro. |
| Problemas detectados | 133 open issues. Varios sobre deprecations de PHP 8.4 y 8.5. 4 security advisories historicos. Codigo legacy (soporta PHP 5.6). Ultima release: octubre 2025. |
| Wrapper recomendado | `mews/purifier` v3.4.4 (abril 2026, compatible L12, 18M installs). Proporciona casts de Eloquent, facade, y mejor integracion Laravel. |

**Analisis honesto de la eleccion:**

HTMLPurifier es la herramienta correcta para el caso de uso (almacenar HTML rico de usuarios de forma segura). No hay alternativa PHP que ofrezca el mismo nivel de filtrado con whitelists configurables. Las opciones son:

| Opcion | Ventaja | Desventaja |
|---|---|---|
| `ezyang/htmlpurifier` directo | Sin dependencias extra | Sin integracion Laravel. Uso manual. |
| `mews/purifier` (wrapper) | Casts Eloquent, facade, config Laravel | Dependencia extra. Por debajo usa HTMLPurifier (mismos problemas). |
| `strip_tags()` + `htmlspecialchars()` | Simple, nativo PHP | Pierde formato rico. No sirve para WYSIWYG. |
| DOMPurify (JS, frontend) | Moderno, rapido | Solo limpia en cliente. No protege si el dato llega por API. |
| Content Security Policy (CSP) | Proteccion a nivel navegador | No limpia el dato almacenado. Complemento, no sustituto. |

**Decision recomendada:** Usar `mews/purifier` (que internamente usa `ezyang/htmlpurifier`). Razones:

1. Integracion nativa con Laravel (service provider auto-discovered).
2. Casts de Eloquent (`CleanHtml`, `CleanHtmlInput`, `CleanHtmlOutput`) que limpian automaticamente al leer/escribir.
3. Configuracion centralizada en `config/purifier.php`.
4. Permite definir perfiles de limpieza (ej. `event_description`, `ticket_note`, `simple_text`).

**Estrategia de mitigacion del riesgo:**

```
1. Instalar mews/purifier (no ezyang/htmlpurifier directamente)
2. Definir perfiles estrictos: solo permitir tags necesarios para descripciones
3. Monitorizar actualizaciones de ezyang/htmlpurifier para patches de seguridad
4. Si HTMLPurifier se abandona, migrar a una solucion custom basada en DOMDocument + whitelist
5. Complementar con CSP headers en el frontend
```

**Campos que requieren purificacion:**

| Modelo | Campo | Perfil |
|---|---|---|
| `Event` | `description` | `event_description` (HTML rico) |
| `Event` | `short_description` | `simple_text` (solo texto) |
| `Product` | `description` | `event_description` |
| `Organizer` | `description` | `event_description` |
| `Page` | `content` | `page_content` (HTML amplio) |
| `Attendee` | `custom_answers` | `simple_text` |

**Integracion en el proyecto:**

```php
// Ejemplo con cast de Eloquent (mews/purifier)
use Mews\Purifier\Casts\CleanHtmlInput;

class Event extends Model
{
    protected $casts = [
        'description' => CleanHtmlInput::class,  // limpia al escribir
        'short_description' => 'string',          // strip_tags en mutator
    ];
}

// O en Actions, antes de persistir:
use Mews\Purifier\Facades\Purifier;

$cleanDescription = Purifier::clean($dto->description, 'event_description');
```

**Fase de instalacion:** Fase 1 (Fundacion), junto con los primeros modelos que tienen campos de texto rico.

---

### 2.3 livewire/livewire v4.3.1 + livewire/volt v1.10.5

**Veredicto: APROBADO sin reservas. Excelente eleccion para MVP.**

| Aspecto | Detalle |
|---|---|
| Que hace | Livewire: componentes interactivos server-side sin escribir JS. Volt: API funcional para Livewire con componentes de un solo archivo (PHP + Blade). |
| Por que encaja | El boilerplate ya tiene Tailwind CSS 4 + Vite. Livewire + Volt es la combinacion natural. Permite construir el panel de organizador, el backoffice y la experiencia de compra sin necesidad de un frontend React separado. |
| Riesgo | Medio-bajo. Livewire v4 es relativamente nuevo (2026). Puede tener breaking changes menores. |
| Alternativas descartadas | React + TypeScript (mas complejo para MVP, requiere API-first completo), Inertia.js (buena alternativa pero añade capa extra) |

**Impacto en la arquitectura del proyecto:**

La eleccion de Livewire + Volt cambia significativamente la estrategia de frontend respecto a la propuesta DDD original:

| Propuesta DDD original | Con Livewire + Volt |
|---|---|
| API-first, React + TypeScript | Server-rendered + interactividad Livewire |
| Controllers devuelven JSON (Resources) | Componentes Livewire renderizan Blade |
| Frontend separado en `frontend/` | Todo en el mismo proyecto Laravel |
| Necesita CORS, Sanctum SPA mode | No necesita CORS (same-origin) |
| Deploy frontend independiente | Deploy unico |

**Estructura de componentes Livewire/Volt:**

```
resources/views/
├── livewire/                          # Componentes Livewire (Volt)
│   ├── auth/
│   │   ├── login.blade.php            # Volt: PHP + Blade en un archivo
│   │   ├── register.blade.php
│   │   └── forgot-password.blade.php
│   │
│   ├── events/
│   │   ├── event-list.blade.php       # Listado publico de eventos
│   │   ├── event-detail.blade.php     # Detalle publico + compra
│   │   ├── event-dashboard.blade.php  # Dashboard del organizador
│   │   ├── event-form.blade.php       # Crear/editar evento
│   │   └── event-settings.blade.php   # Configuracion del evento
│   │
│   ├── products/
│   │   ├── product-list.blade.php     # Tipos de entrada del evento
│   │   ├── product-form.blade.php     # Crear/editar tipo de entrada
│   │   └── product-pricing.blade.php  # Configurar precios/tiers
│   │
│   ├── orders/
│   │   ├── checkout.blade.php         # Flujo de compra
│   │   ├── order-confirmation.blade.php
│   │   └── my-orders.blade.php        # Historial del asistente
│   │
│   ├── attendees/
│   │   ├── attendee-list.blade.php    # Lista de asistentes
│   │   ├── check-in.blade.php         # Escaneo QR
│   │   └── export-attendees.blade.php
│   │
│   ├── organizer/
│   │   ├── organizer-dashboard.blade.php
│   │   ├── organizer-settings.blade.php
│   │   └── team-management.blade.php
│   │
│   ├── reporting/
│   │   ├── sales-report.blade.php
│   │   └── dashboard-metrics.blade.php
│   │
│   └── admin/
│       ├── admin-dashboard.blade.php
│       ├── user-management.blade.php
│       └── platform-settings.blade.php
│
├── layouts/
│   ├── app.blade.php                  # Layout principal (asistente)
│   ├── organizer.blade.php            # Layout panel organizador
│   └── admin.blade.php                # Layout backoffice
│
└── components/                        # Componentes Blade reutilizables
    ├── ui/
    │   ├── button.blade.php
    │   ├── modal.blade.php
    │   ├── table.blade.php
    │   └── alert.blade.php
    └── event/
        ├── event-card.blade.php
        └── ticket-card.blade.php
```

**Ejemplo de componente Volt:**

```php
<?php
// resources/views/livewire/events/event-form.blade.php

use App\Actions\Event\CreateEvent;
use App\Actions\Event\UpdateEvent;
use App\DataTransferObjects\Event\CreateEventDto;
use App\Models\Event;
use Livewire\Volt\Component;

new class extends Component {
    public ?Event $event = null;
    public string $title = '';
    public string $description = '';
    public string $startDate = '';
    public string $endDate = '';
    public ?int $venueId = null;
    public ?int $categoryId = null;
    public ?int $capacity = null;

    public function mount(?Event $event = null): void
    {
        if ($event) {
            $this->event = $event;
            $this->title = $event->title;
            $this->description = $event->description ?? '';
            $this->startDate = $event->start_date->format('Y-m-d\TH:i');
            $this->endDate = $event->end_date->format('Y-m-d\TH:i');
            $this->venueId = $event->venue_id;
            $this->categoryId = $event->category_id;
            $this->capacity = $event->capacity;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'startDate' => ['required', 'date', 'after:now'],
            'endDate' => ['required', 'date', 'after:startDate'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $dto = new CreateEventDto(
            title: $this->title,
            description: $this->description,
            startDate: $this->startDate,
            endDate: $this->endDate,
            venueId: $this->venueId,
            categoryId: $this->categoryId,
            capacity: $this->capacity,
            organizerId: current_organizer_id(),
        );

        if ($this->event) {
            app(UpdateEvent::class)->execute($this->event, $dto);
            $this->dispatch('event-updated');
        } else {
            $event = app(CreateEvent::class)->execute($dto);
            $this->dispatch('event-created', eventId: $event->event_id);
        }
    }
};
?>

<div>
    <form wire:submit="save">
        <x-ui.input wire:model="title" label="Event title" />
        <x-ui.textarea wire:model="description" label="Description" />
        <div class="grid grid-cols-2 gap-4">
            <x-ui.input wire:model="startDate" type="datetime-local" label="Start" />
            <x-ui.input wire:model="endDate" type="datetime-local" label="End" />
        </div>
        <x-ui.input wire:model="capacity" type="number" label="Capacity" />
        <x-ui.button type="submit">
            {{ $event ? 'Update event' : 'Create event' }}
        </x-ui.button>
    </form>
</div>
```

**Convivencia con la arquitectura Actions/DTOs:**

```
Livewire Component (Volt)
    │
    ├── mount() → carga datos iniciales
    ├── validate() → validacion de formulario
    ├── save() → crea DTO → invoca Action → Action ejecuta logica
    │
    └── render() → devuelve vista Blade

El Action sigue siendo la pieza central de logica de negocio.
El componente Livewire es solo la capa de presentacion interactiva.
```

**Fase de instalacion:** Fase 1 (Fundacion). Es el frontend del MVP.

---

### 2.4 spatie/laravel-activitylog v5.0.0

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Registra automaticamente cambios en modelos Eloquent: quien hizo que, cuando, que cambio (old/new values). |
| Por que encaja | Reemplaza el `AuditLog` custom propuesto en la propuesta DDD. Es mas robusto, probado y maintained. |
| Riesgo | Minimo. Spatie mantiene activamente. v5.0.0 requiere PHP 8.4 y L12 (exactamente nuestro stack). |
| Alternativas descartadas | OwenIt/Auditing (menos maintained), implementacion custom (reinventar la rueda) |

**Integracion en el proyecto:**

```php
// En modelos que requieren trazabilidad:
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Event extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'start_date', 'end_date', 'capacity'])
            ->logOnlyDirty()          // solo cambios reales
            ->dontSubmitEmptyLogs();  // no loguear si no hay cambios
    }
}

// Modelos que deben tener activity log:
// - Event (cambios de estado, ediciones)
// - Product (cambios de precio, quota)
// - Order (cambios de estado)
// - Payment (cambios de estado)
// - Organizer (cambios de configuracion)
// - User (cambios de perfil, roles)
// - PromoCode (creacion, desactivacion)
// - Invoice (emision, cancelacion)

// Consultas de actividad:
use Spatie\Activitylog\Models\Activity;

// Todas las actividades de un evento
Activity::forSubject($event)->get();

// Actividades de un usuario
Activity::causedBy($user)->get();

// Actividades recientes del organizador
Activity::forSubject($organizer)
    ->latest()
    ->limit(50)
    ->get();
```

**Relacion con la propuesta DDD:**

| Propuesta DDD | Con activitylog |
|---|---|
| Tabla `audit_logs` custom | Tabla `activity_log` de Spatie (mas rica) |
| Modelo `AuditLog` custom | Modelo `Activity` de Spatie |
| Logging manual en Actions | Logging automatico via trait `LogsActivity` |

**Fase de instalacion:** Fase 1 (Fundacion). Se activa desde el primer modelo.

---

### 2.5 spatie/laravel-multitenancy v4.1.3

**Veredicto: APROBADO. Integrado en Sprint T0 con modelo tenant-aware single DB.**

| Aspecto | Detalle |
|---|---|
| Que hace | Hace la aplicacion tenant-aware. Identifica el tenant actual por request, permite cambiar conexion DB, y proporciona herramientas para jobs y comandos multi-tenant. |
| Por que encaja | Cada organizador puede resolver su contexto por host/dominio propio sin separar la base de datos. Los datos de cada organizador se aislan por tenant. |
| Riesgo | Medio. Multitenancy añade complejidad significativa. No es trivial de implementar correctamente. |
| Alternativas | `stancl/tenancy` (mas opinionado, mas features), implementacion manual con middleware + scope |

**Decision de modelo de tenancy:**

Para una plataforma de eventos, hay tres modelos posibles:

| Modelo | Descripcion | Ventaja | Desventaja |
|---|---|---|---|
| **Single DB + tenant_id** | Todos los datos en una DB, filtrados por `organizer_id` | Simple, barato, facil de migrar | Menor aislamiento, queries mas complejas |
| **Multi DB (una DB por tenant)** | Cada organizador tiene su propia base de datos | Maximo aislamiento, backup independiente | Complejidad operativa, coste infraestructura |
| **Hibrido** | Landlord DB (tenants, users, config global) + Tenant DB (datos del evento) | Balance entre aislamiento y simplicidad | Dos conexiones por request |

**Recomendacion para este proyecto:**

```
Estado actual / decision vigente: Single DB + tenant_id (organizer_id como scope)
  - El proyecto sigue siendo tenant-aware con una sola BBDD
  - Usar Global Scopes de Eloquent y middleware organizer.detect para aislar por organizer
  - Mantener Organizer.domain como branding/routing opcional, no como señal de DB separada

Sprint T0 ejecutado: integrar spatie/laravel-multitenancy en modo single database
  - Resolver tenant por el host raíz configurado por `APP_URL` primero
  - Mantener fallback compatible con rutas internas por organizer
  - Preparar jobs/listeners para contexto tenant-aware
```

**Como funciona la identificacion por dominio:**

```php
// config/multitenancy.php
'tenant_finder' => OrganizerTenantFinder::class,

// El middleware identifica el tenant por el host HTTP:
// APP_URL host → contexto superadmin sin tenant
// eventos.acme.com → tenant: Acme Corp (organizer_id: 42)
// tickets.beta-events.com → tenant: Beta Events (organizer_id: 17)
// rutas internas /organizers/{organizer} → fallback compatible
```

**Integracion con el modelo Organizer:**

```
El modelo Organizer ES el tenant.
Cada Organizer tiene:
  - Su propio dominio (o subdominio)
  - Su propia configuracion de marca
  - Sus propios eventos, productos, pedidos
  - Su propio equipo de usuarios

En la implementacion actual:
  - Global Scope / queries con organizer_id filtran el tenant
  - Middleware organizer.detect establece el organizer actual por ruta/sesion
  - Organizer.domain se usa como dato de branding y para UX, no para cambiar la conexion DB

Si en el futuro se aprueba multi-DB:
  - Spatie Multitenancy cambiaria la conexion DB por tenant
  - Los modelos de tenant usarian la conexion del tenant actual
```

**Fase de instalacion:**
- **Sprint T0:** integrar la libreria en single DB + organizer_id.
- **No activar multi-DB físico.** Si se necesitara en el futuro, será una nueva decision de arquitectura.

---

### 2.6 spatie/laravel-permission v8.0.0

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Gestion de roles y permisos sobre Eloquent. Soporta guards, teams, cache. |
| Por que encaja | El boilerplate no tiene sistema de roles/permisos. Spatie Permission es el estandar del ecosistema Laravel (101M installs). |
| Riesgo | Minimo. v8.0.0 es reciente (mayo 2026), requiere PHP 8.3+ y L12+. |
| Alternativas descartadas | Implementacion custom (reinventar), Bouncer (menos maintained) |

**Roles y permisos propuestos para el proyecto:**

> **SUPERSEDED by A13 (organizer-scoped roles):** The original proposal below listed `organizer_admin`, `organizer_editor`, `organizer_viewer` as global Spatie roles. This was revised: organizer team roles are now stored as a string column (`role`) on the `organizer_user` pivot table, managed via the `App\Support\Organizers\OrganizerRoles` enum. Spatie Permission is only used for global roles (`super_admin`, `platform_admin`, `attendee`). See migration `2026_06_27_000001_change_organizer_user_role_id_to_role_string.php` and `App\Support\Organizers\OrganizerRoles` for the current implementation.

```php
// Roles del sistema (ACTUALIZADO post A13):
'super_admin'          // Admin de la plataforma (Spatie global)
'platform_admin'       // Gestiona contenido y moderacion (Spatie global)
'attendee'             // Usuario que compra entradas (Spatie global)

// Organizer team roles (NO son Spatie roles — son strings en pivot organizer_user):
// admin, editor, viewer — gestionados via App\Support\Organizers\OrganizerRoles enum

// Permisos granulares (Spatie):
'event.create'
'event.update'
'event.delete'
'event.publish'
'event.cancel'
'product.manage'
'product.pricing'
'order.view'
'order.refund'
'attendee.manage'
'attendee.check-in'
'report.view'
'report.export'
'notification.send'
'settings.manage'
'team.manage'
'invoice.view'
'webhook.manage'

// Asignacion de permisos por rol global (ejemplo):
$role = Role::create(['name' => 'platform_admin']);
$role->givePermissionTo([
    'event.create', 'event.update', 'event.delete', 'event.publish', 'event.cancel',
    'product.manage', 'product.pricing',
    'order.view', 'order.refund',
    'attendee.manage', 'attendee.check-in',
    'report.view', 'report.export',
    'notification.send',
    'settings.manage',
    'team.manage',
    'invoice.view',
    'webhook.manage',
]);

// En policies:
class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $user->can('event.update')
            && $user->belongsToOrganizer($event->organizer_id);
    }
}

// Organizer team role check (NO usa Spatie):
// $member->pivot->role === OrganizerRoles::Admin->value
```

**Integracion con Sanctum:**

```
Sanctum autentica al usuario → Spatie Permission autoriza la accion.

Flow:
  1. Request entra → Sanctum verifica identidad (cookie o token)
  2. Middleware verifica rol/permiso → Spatie Permission
  3. Policy verifica acceso al recurso especifico → Laravel Policy
  4. Action ejecuta la logica de negocio
```

**Fase de instalacion:** Fase 1 (Fundacion), junto con Sanctum.

---

### 2.7 bacon/bacon-qr-code v2.0

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Genera códigos QR vectoriales (SVG) de alta precisión. |
| Por que encaja | Requisito para generar códigos QR únicos para las entradas de los asistentes, los cuales se renderizan en formato PDF y se escanean en el acceso. |
| Alternativas | Librerías JavaScript en frontend (menos seguras/idempotentes). |

**Fase de instalacion:** Fase 2 (Sprint 2.4).

---

### 2.8 dompdf/dompdf (via barryvdh/laravel-dompdf v3.0)

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Compila y renderiza archivos PDF a partir de código HTML y CSS. |
| Por que encaja | Para la exportación de entradas y facturas en formato PDF desde las plantillas Blade del servidor de forma automatizada. |
| Alternativas | Snappy/WKHTMLTOPDF (requiere binarios en el sistema), Browsershot (requiere Node/Puppeteer, más pesado). |

**Fase de instalacion:** Fase 2 (Sprint 2.4).

---

### 2.9 html5-qrcode v2.3.8 (JS)

**Veredicto: APROBADO sin reservas.**

| Aspecto | Detalle |
|---|---|
| Que hace | Lector de códigos de barras y QR en tiempo real para navegadores web utilizando la cámara del dispositivo de forma local. |
| Por que encaja | Permite el escaneo rápido de entradas desde el panel de control del organizador sin necesidad de instalar apps nativas. |
| Alternativas | Instascan (obsoleto), QuaggaJS (enfocado a código de barras). |

**Fase de instalacion:** Fase 3 (Sprint 3.1).

---

## 3. Orden de instalacion y dependencias

> **Estado: Fase 1 completada (Sprint 1.1).** Las librerias 1–5 estan instaladas, configuradas y con migraciones desplegadas. El bloque de Fase 4 (multitenancy) permanece sin instalar, segun la recomendacion.

```
Fase 1 — Fundacion (semana 1-2)
│
├── 1. spatie/laravel-permission v8.0.0
│   └── Tablas: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
│
├── 2. laravel/sanctum v4.3.2
│   └── Tabla: personal_access_tokens
│   └── Config: sanctum.php, cors.php, session.php
│
├── 3. spatie/laravel-activitylog v5.0.0
│   └── Tabla: activity_log
│   └── Se activa en cada modelo con trait LogsActivity
│
├── 4. mews/purifier v3.4.4 (wrapper de ezyang/htmlpurifier)
│   └── Config: purifier.php
│   └── Se usa en casts de modelos con campos HTML ricos
│
└── 5. livewire/livewire v4.3.1 + livewire/volt v1.10.5
    └── Componentes en resources/views/livewire/
    └── Layouts en resources/views/layouts/

Fase 4 — SaaS avanzado (semana 13+)
│
└── 6. spatie/laravel-multitenancy v4.1.3
    └── Tabla: tenants (landlord DB)
    └── Config: multitenancy.php
    └── Middleware: IdentifyTenant
```

---

## 4. Impacto en la propuesta DDD original

| Cambio | Antes (propuesta DDD) | Ahora (con librerias) |
|---|---|---|
| Auth | Sin definir | Sanctum (cookie + token) |
| Roles/Permisos | Implementacion custom | Spatie Permission |
| Audit/Activity log | Tabla `audit_logs` custom | Spatie Activitylog |
| HTML sanitization | No definido | mews/purifier (casts Eloquent) |
| Frontend MVP | React + TypeScript (propuesta) | Livewire + Volt + Tailwind |
| Multi-tenancy | No definido en fase inicial | Spatie Multitenancy (Fase 4) |
| API-first | Si, desde el inicio | Livewire para MVP. API REST se mantiene para mobile/integraciones. |

---

## 5. Comando de instalacion completo (Fase 1)

```bash
# Dentro de Sail:
vendor/bin/sail composer require \
    laravel/sanctum \
    spatie/laravel-permission \
    spatie/laravel-activitylog \
    mews/purifier \
    livewire/livewire \
    livewire/volt

# Publicar configuraciones:
vendor/bin/sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
vendor/bin/sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
vendor/bin/sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
vendor/bin/sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
vendor/bin/sail artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"

# Instalar Livewire Volt:
vendor/bin/sail artisan volt:install

# Ejecutar migraciones:
vendor/bin/sail artisan migrate

# QA check:
composer qa
```

---

## 6. Resumen de decisiones

> **Estado de instalacion (post Sprint 3.1):** marcado en la columna "Instalado".

| Libreria | Decision | Instalado | Justificacion clave |
|---|---|---|---|
| `laravel/sanctum` | Instalar en Fase 1 | Si (Sprint 1.1) | Auth oficial de Laravel. Simple, probado, compatible. |
| `ezyang/htmlpurifier` | Usar via `mews/purifier` | Si (via `mews/purifier`) | Wrapper Laravel con casts Eloquent. HTMLPurifier es la unica opcion PHP robusta para filtrado HTML con whitelists. |
| `livewire/livewire` | Instalar en Fase 1 | Si (Sprint 1.1) | Frontend interactivo sin JS separado. Ideal para MVP con Tailwind. |
| `livewire/volt` | Instalar en Fase 1 | Si (Sprint 1.1) | Componentes funcionales de un archivo. DX excelente. |
| `spatie/laravel-activitylog` | Instalar en Fase 1 | Si (Sprint 1.1) | Trazabilidad automatica. Reemplaza audit log custom. |
| `spatie/laravel-multitenancy` | **No instalar hasta Fase 4** | No | Complejidad innecesaria para MVP. Usar scopes de Eloquent primero. |
| `spatie/laravel-permission` | Instalar en Fase 1 | Si (Sprint 1.1) | Estandar de la industria. 101M installs. Compatible con Sanctum. |
| `bacon/bacon-qr-code` | Instalar en Fase 2 | Si (Sprint 2.4) | Indispensable para generación robusta de códigos QR vectoriales de entradas. |
| `barryvdh/laravel-dompdf` | Instalar en Fase 2 | Si (Sprint 2.4) | Estandar de facto para compilación rápida de PDFs en Laravel. |
| `html5-qrcode` | Instalar en Fase 3 | Si (Sprint 3.1) | Lector de cámara web ligero directo en frontend para validaciones de accesos. |

---

## 7. Riesgos globales del stack propuesto

| Riesgo | Probabilidad | Impacto | Mitigacion |
|---|---|---|---|
| HTMLPurifier deprecations PHP 8.5+ | Media | Medio | Monitorizar releases. Tener plan de migracion a solucion custom. |
| Livewire v4 breaking changes | Baja | Medio | Pin version. Leer changelog antes de actualizar. |
| Multitenancy prematura | Alta | Alto | No instalar hasta Fase 4. Scopes primero. |
| Acoplamiento Livewire-Backend | Media | Medio | Mantener Actions como logica de negocio. Livewire solo presentacion. |
| Spatie Permission cache invalidation | Baja | Bajo | Configurar cache correctamente. Limpiar cache al cambiar roles. |

---

*Fin del documento de valoracion.*
