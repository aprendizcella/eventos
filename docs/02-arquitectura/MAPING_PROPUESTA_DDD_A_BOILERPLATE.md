# Mapeo: Propuesta DDD → Boilerplate Laravel 12 existente

## Documento de adaptacion tecnica

> **Documento histórico / estado de referencia:** este mapeo conserva la propuesta y sus decisiones de adaptación iniciales. El repositorio ya materializó gran parte del mapa hasta Sprint 6.1; para el estado vigente y los pendientes debe consultarse [`ESTADO_EJECUCION.md`](../00-estado/ESTADO_EJECUCION.md). Las listas de clases y carpetas de este documento son referencias de diseño, no un inventario actual exhaustivo.

**Proposito:** Mostrar como la propuesta tecnica DDD (`PROPUESTA_TECNICA_DDD_LARAVEL12.md`) encaja en el boilerplate Laravel 12 existente, respetando sus convenciones, stack y flujo de trabajo.

> **Estado de ejecucion (referencia post Sprint 1.1):** El mapeo se materializó progresivamente en los sprints posteriores: identidad, eventos, organizadores, ticketing, operación, monetización, discovery y backoffice tienen implementación en el repositorio. Las partes no implementadas o diferidas deben leerse en el plan vigente; este documento no debe interpretarse como que todo el mapa sigue pendiente.

---

## 1. Diferencias entre la propuesta DDD y el boilerplate

| Aspecto | Propuesta DDD | Boilerplate actual | Decision de adaptacion |
|---|---|---|---|
| **DB** | PostgreSQL 16 | MariaDB 11 | Usar MariaDB. JSON en vez de JSONB. Funciones MySQL en vez de PostgreSQL-specific. |
| **Estructura** | Carpetas por Bounded Context (app/EventManagement/Domain/, etc.) | Estructura plana por tipo (app/Actions/, app/Http/, app/Models/) | **Mantener estructura del boilerplate.** Organizar por dominio dentro de cada tipo. |
| **Flujo escritura** | Command → Handler → Repository | FormRequest → DTO → Controller → Action | **Usar flujo del boilerplate.** Action = Handler. |
| **Flujo lectura** | Query → Handler → ReadModel | Controller → ViewModel/Resource | **Usar flujo del boilerplate.** |
| **CQRS** | Commands/Queries separados | Actions (escritura) + ViewModels/Resources (lectura) | **Mantener convencion del boilerplate.** |
| **Domain Events** | Interface propia + dispatcher | Laravel Events system nativo | **Usar Laravel Events.** |
| **Repositories** | Interface en Domain, impl en Infrastructure | Opcional, en `app/Repositories/{Dominio}/` con Contracts/ | **Usar convencion del boilerplate.** |
| **Auth** | Sanctum + JWT | Laravel built-in (sin paquete auth) | Instalar Sanctum en Fase 1. |
| **Frontend** | React + TypeScript + Vite | Tailwind CSS 4 + Vite (Blade) | Blade + Tailwind para MVP. React en fase posterior si aplica. |
| **Testing** | Pest + Playwright | Pest 4.x | Pest para todo. Playwright solo si hay frontend React. |
| **PHP** | 8.3+ | 8.4 | PHP 8.4 (mejor). |
| **ID** | UUID v7 | Convencion `{model}_id` (int/bigint por defecto) | Evaluar UUID vs autoincrement. MariaDB soporta UUID. |
| **QA** | PHPStan + Deptrac | Rector + Pint + PHPStan L8 + Pest + SonarQube | **Mantener pipeline del boilerplate.** Anadir Deptrac si se desea. |

---

## 2. Estructura de carpetas adaptada

La propuesta DDD se adapta a la estructura del boilerplate organizando por **dominio funcional** dentro de cada tipo de componente.

```
app/
├── Actions/                              # Logica de escritura (casos de uso)
│   ├── Auth/                             # Identity & Access
│   │   ├── RegisterUser.php
│   │   ├── LoginUser.php
│   │   ├── RequestPasswordReset.php
│   │   └── VerifyEmail.php
│   │
│   ├── Event/                            # Event Management
│   │   ├── CreateEvent.php
│   │   ├── UpdateEvent.php
│   │   ├── PublishEvent.php
│   │   ├── CancelEvent.php
│   │   └── CloneEvent.php
│   │
│   ├── Organizer/                        # Organizer
│   │   ├── CreateOrganizer.php
│   │   ├── UpdateOrganizer.php
│   │   ├── AddTeamMember.php
│   │   └── RemoveTeamMember.php
│   │
│   ├── Product/                          # Ticketing & Products
│   │   ├── CreateProduct.php
│   │   ├── UpdateProduct.php
│   │   ├── SetProductPricing.php
│   │   └── CreatePromoCode.php
│   │
│   ├── Order/                            # Ordering & Checkout
│   │   ├── CreateOrder.php
│   │   ├── ReserveStock.php
│   │   ├── ApplyPromoCode.php
│   │   ├── ProcessCheckout.php
│   │   ├── ConfirmOrder.php
│   │   ├── CancelOrder.php
│   │   └── ReleaseExpiredReservations.php
│   │
│   ├── Payment/                          # Payment
│   │   ├── InitiatePayment.php
│   │   ├── HandleStripeWebhook.php
│   │   ├── ProcessRefund.php
│   │   └── CalculatePayout.php
│   │
│   ├── Attendee/                         # Attendee & Check-in
│   │   ├── GenerateAttendeeQr.php
│   │   ├── CheckInAttendee.php
│   │   ├── UndoCheckIn.php
│   │   └── ExportAttendeeList.php
│   │
│   ├── Notification/                     # Notification
│   │   ├── SendOrderConfirmation.php
│   │   ├── SendEventReminder.php
│   │   ├── SendBulkMessage.php
│   │   └── DeliverWebhook.php
│   │
│   ├── Invoice/                          # Invoicing & Fiscal
│   │   ├── GenerateInvoice.php
│   │   ├── IssueCreditNote.php
│   │   └── DownloadInvoicePdf.php
│   │
│   └── Admin/                            # Administration
│       ├── ManageUsers.php
│       ├── ModerateEvents.php
│       └── ConfigurePlatformFees.php
│
├── DataTransferObjects/                  # DTOs por dominio
│   ├── Auth/
│   │   ├── RegisterUserDto.php
│   │   └── LoginUserDto.php
│   ├── Event/
│   │   ├── CreateEventDto.php
│   │   └── UpdateEventDto.php
│   ├── Organizer/
│   │   └── CreateOrganizerDto.php
│   ├── Product/
│   │   ├── CreateProductDto.php
│   │   └── SetProductPricingDto.php
│   ├── Order/
│   │   ├── CreateOrderDto.php
│   │   └── OrderItemDto.php
│   ├── Payment/
│   │   └── ProcessPaymentDto.php
│   ├── Attendee/
│   │   └── CheckInAttendeeDto.php
│   └── Invoice/
│       └── GenerateInvoiceDto.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── RegisterController.php
│   │   │   ├── LoginController.php
│   │   │   └── ForgotPasswordController.php
│   │   ├── Event/
│   │   │   ├── EventController.php          # Resource CRUD
│   │   │   └── PublishEventController.php   # Invocable
│   │   ├── Organizer/
│   │   │   └── OrganizerController.php
│   │   ├── Product/
│   │   │   └── ProductController.php
│   │   ├── Order/
│   │   │   ├── OrderController.php
│   │   │   └── CheckoutController.php
│   │   ├── Payment/
│   │   │   ├── PaymentController.php
│   │   │   └── StripeWebhookController.php
│   │   ├── Attendee/
│   │   │   ├── AttendeeController.php
│   │   │   └── CheckInController.php
│   │   ├── Notification/
│   │   │   └── MessageController.php
│   │   ├── Invoice/
│   │   │   └── InvoiceController.php
│   │   ├── Discovery/
│   │   │   ├── SearchEventsController.php
│   │   │   └── PublicEventController.php
│   │   ├── Reporting/
│   │   │   └── ReportController.php
│   │   └── Admin/
│   │       └── AdminController.php
│   │
│   ├── Requests/                          # FormRequests por dominio
│   │   ├── Auth/
│   │   │   ├── RegisterRequest.php
│   │   │   └── LoginRequest.php
│   │   ├── Event/
│   │   │   ├── StoreEventRequest.php
│   │   │   └── UpdateEventRequest.php
│   │   ├── Organizer/
│   │   │   ├── StoreOrganizerRequest.php
│   │   │   └── UpdateOrganizerRequest.php
│   │   ├── Product/
│   │   │   ├── StoreProductRequest.php
│   │   │   └── UpdateProductRequest.php
│   │   ├── Order/
│   │   │   ├── StoreOrderRequest.php
│   │   │   └── CancelOrderRequest.php
│   │   ├── Payment/
│   │   │   └── ProcessPaymentRequest.php
│   │   └── Attendee/
│   │       └── CheckInRequest.php
│   │
│   └── Resources/                         # API Resources por dominio
│       ├── Auth/
│       │   └── UserResource.php
│       ├── Event/
│       │   ├── EventResource.php
│       │   └── EventCollection.php
│       ├── Organizer/
│       │   └── OrganizerResource.php
│       ├── Product/
│       │   └── ProductResource.php
│       ├── Order/
│       │   ├── OrderResource.php
│       │   └── OrderItemResource.php
│       ├── Attendee/
│       │   └── AttendeeResource.php
│       └── Invoice/
│           └── InvoiceResource.php
│
├── ViewModels/                            # ViewModels por dominio (lectura compleja)
│   ├── Event/
│   │   ├── EventDashboardViewModel.php
│   │   └── EventDetailViewModel.php
│   ├── Order/
│   │   └── CheckoutViewModel.php
│   ├── Organizer/
│   │   └── OrganizerDashboardViewModel.php
│   └── Reporting/
│       └── SalesReportViewModel.php
│
├── Models/                                # Eloquent Models (aggregates practicos)
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── Organizer.php
│   ├── Event.php
│   ├── Category.php
│   ├── Venue.php
│   ├── Product.php
│   ├── ProductPrice.php
│   ├── PromoCode.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Attendee.php
│   ├── CheckInList.php
│   ├── Payment.php
│   ├── Refund.php
│   ├── Payout.php
│   ├── Invoice.php
│   ├── NotificationTemplate.php
│   ├── NotificationLog.php
│   ├── Webhook.php
│   ├── WebhookDelivery.php
│   ├── WaitlistEntry.php
│   ├── Affiliate.php
│   └── AuditLog.php
│
├── Enums/                                 # Enums PHP 8.4 (estados, tipos)
│   ├── EventStatus.php                    # draft, configured, published, paused, completed, cancelled
│   ├── EventVisibility.php                # public, private, password_protected
│   ├── ProductType.php                    # ticket, addon, merchandise, donation
│   ├── OrderStatus.php                    # pending, reserved, paid, confirmed, cancelled, expired, refunded
│   ├── PaymentStatus.php                  # pending, completed, failed, refunded, partially_refunded
│   ├── PaymentMethod.php                  # stripe, paypal, offline
│   ├── RefundStatus.php                   # pending, completed, failed
│   ├── AttendeeStatus.php                 # active, cancelled, checked_in
│   ├── InvoiceStatus.php                  # issued, cancelled
│   ├── WaitlistStatus.php                 # waiting, notified, expired, converted
│   ├── PromoCodeType.php                  # percentage, fixed
│   └── WebhookEventType.php              # order.confirmed, payment.completed, etc.
│
├── Events/                                # Domain Events (Laravel Events)
│   ├── Auth/
│   │   ├── UserRegistered.php
│   │   └── UserVerifiedEmail.php
│   ├── Event/
│   │   ├── EventCreated.php
│   │   ├── EventPublished.php
│   │   ├── EventUpdated.php
│   │   ├── EventCancelled.php
│   │   └── EventCompleted.php
│   ├── Product/
│   │   ├── ProductCreated.php
│   │   ├── ProductUpdated.php
│   │   └── ProductSoldOut.php
│   ├── Order/
│   │   ├── OrderCreated.php
│   │   ├── OrderReserved.php
│   │   ├── OrderConfirmed.php
│   │   ├── OrderCancelled.php
│   │   └── OrderExpired.php
│   ├── Payment/
│   │   ├── PaymentCompleted.php
│   │   ├── PaymentFailed.php
│   │   ├── RefundProcessed.php
│   │   └── PayoutCreated.php
│   ├── Attendee/
│   │   ├── AttendeeCheckedIn.php
│   │   └── CheckInRejected.php
│   ├── Organizer/
│   │   ├── OrganizerCreated.php
│   │   ├── TeamMemberAdded.php
│   │   └── TeamMemberRemoved.php
│   └── Invoice/
│       └── InvoiceGenerated.php
│
├── Listeners/                             # Event Listeners
│   ├── Order/
│   │   ├── GenerateAttendeesOnOrderConfirmed.php
│   │   ├── GenerateInvoiceOnOrderConfirmed.php
│   │   ├── ReleaseStockOnOrderCancelled.php
│   │   └── ReleaseStockOnOrderExpired.php
│   ├── Payment/
│   │   ├── ConfirmOrderOnPaymentCompleted.php
│   │   └── NotifyOnPaymentFailed.php
│   ├── Event/
│   │   ├── IndexEventOnPublished.php
│   │   └── NotifyAttendeesOnEventCancelled.php
│   ├── Product/
│   │   └── NotifyWaitlistOnProductSoldOut.php
│   └── Notification/
│       └── SendEmailOnOrderConfirmed.php
│
├── Repositories/                          # Repositories (opcional, cuando aplique)
│   ├── Event/
│   │   ├── Contracts/
│   │   │   └── EventRepository.php        # Interface
│   │   └── EloquentEventRepository.php    # Implementacion
│   ├── Order/
│   │   ├── Contracts/
│   │   │   └── OrderRepository.php
│   │   └── EloquentOrderRepository.php
│   ├── Product/
│   │   ├── Contracts/
│   │   │   └── ProductRepository.php
│   │   └── EloquentProductRepository.php
│   └── Attendee/
│       ├── Contracts/
│       │   └── AttendeeRepository.php
│       └── EloquentAttendeeRepository.php
│
├── Services/                              # Servicios reutilizables
│   ├── Pricing/
│   │   ├── PriceCalculator.php            # Calcula precios con taxes, descuentos, fees
│   │   └── PromoCodeValidator.php         # Valida aplicabilidad de promo codes
│   ├── Payment/
│   │   ├── PaymentGatewayInterface.php    # Interface para gateways
│   │   ├── StripeGateway.php              # Implementacion Stripe
│   │   └── PayPalGateway.php              # Implementacion PayPal (futuro)
│   ├── Stock/
│   │   └── StockManager.php              # Reserva/libera stock atomicamente
│   ├── QrCode/
│   │   └── QrCodeGenerator.php           # Genera QR para tickets
│   ├── Pdf/
│   │   ├── TicketPdfGenerator.php        # Genera PDF de ticket
│   │   └── InvoicePdfGenerator.php       # Genera PDF de factura
│   └── Commission/
│       └── CommissionCalculator.php      # Calcula comisiones de plataforma
│
├── Policies/                              # Authorization Policies
│   ├── EventPolicy.php
│   ├── OrganizerPolicy.php
│   ├── OrderPolicy.php
│   └── ProductPolicy.php
│
├── Rules/                                 # Custom Validation Rules
│   ├── ValidDateRange.php
│   ├── UniquePromoCodePerEvent.php
│   └── CapacityNotExceeded.php
│
├── Middleware/
│   ├── OrganizerContext.php               # Establece contexto de organizador
│   ├── LocaleMiddleware.php               # i18n
│   └── TimezoneMiddleware.php
│
├── Providers/
│   └── AppServiceProvider.php             # Registro de bindings (repositories, gateways)
│
└── ValueObjects/                          # Value Objects (si se necesitan)
    ├── Money.php                          # amount (int centimos) + currency
    ├── DateRange.php                      # start + end DateTimeImmutable
    └── Email.php                          # Validated email string
```

---

## 3. Mapeo de Bounded Contexts a carpetas del boilerplate

| Bounded Context (DDD) | Carpeta Actions/ | Carpeta Models/ | Carpeta Events/ |
|---|---|---|---|
| Identity & Access | `Actions/Auth/` | `Models/User.php`, `Role.php`, `Permission.php` | `Events/Auth/` |
| Event Management | `Actions/Event/` | `Models/Event.php`, `Category.php`, `Venue.php` | `Events/Event/` |
| Ticketing & Products | `Actions/Product/` | `Models/Product.php`, `ProductPrice.php`, `PromoCode.php` | `Events/Product/` |
| Ordering & Checkout | `Actions/Order/` | `Models/Order.php`, `OrderItem.php` | `Events/Order/` |
| Payment | `Actions/Payment/` | `Models/Payment.php`, `Refund.php`, `Payout.php` | `Events/Payment/` |
| Attendee & Check-in | `Actions/Attendee/` | `Models/Attendee.php`, `CheckInList.php` | `Events/Attendee/` |
| Organizer | `Actions/Organizer/` | `Models/Organizer.php` | `Events/Organizer/` |
| Notification | `Actions/Notification/` | `Models/NotificationTemplate.php`, `NotificationLog.php`, `Webhook.php` | — |
| Invoicing & Fiscal | `Actions/Invoice/` | `Models/Invoice.php` | `Events/Invoice/` |
| Discovery & Search | `Actions/Discovery/` (lectura, usa Services) | Read models via Meilisearch | — |
| Reporting | `Actions/Reporting/` (lectura) | ViewModels | — |
| Administration | `Actions/Admin/` | — | — |

Para Sprint 5.2, la orquestación concreta de búsqueda se ubicará en `app/Services/Discovery/EventSearchService.php`; el modelo `Event` conservará la responsabilidad de definir el payload y la elegibilidad de indexación, no la composición completa de filtros públicos.

---

## 4. Adaptaciones de la propuesta original

### 4.1 MariaDB en vez de PostgreSQL

| Propuesta original (PostgreSQL) | Adaptacion (MariaDB) |
|---|---|
| `JSONB` columns | `JSON` columns (MariaDB 11 soporta JSON nativo) |
| `UUID` PK nativo | `CHAR(36)` para UUIDs o `BIGINT UNSIGNED AUTO_INCREMENT` |
| `SELECT FOR UPDATE` | `LOCK IN SHARE MODE` / `FOR UPDATE` (compatible) |
| Partial indexes | No soportados directamente. Usar indexes condicionales via generated columns. |
| Full-text search nativo | Meilisearch (ya incluido en compose.yaml) |
| `DECIMAL(10,8)` lat/lng | Compatible |

**Decision recomendada para IDs:**

| Opcion | Ventaja | Desventaja |
|---|---|---|
| `BIGINT UNSIGNED AUTO_INCREMENT` | Simple, rapido, nativo MariaDB | Expone conteo total |
| `CHAR(36)` UUID v4 | Seguro, distribuido | Indexes mas lentos, URLs largas |
| `BIGINT` con Hashids/Obfuscate | Simple + URLs no predecibles | Dependencia extra |

**Recomendacion:** Usar `BIGINT UNSIGNED AUTO_INCREMENT` para MVP. Migrar a UUID si el negocio lo requiere (marketplace publico con URLs compartibles).

### 4.2 Flujo de escritura adaptado

```
Propuesta DDD original:
  Command → Handler → Repository → Domain Events

Adaptacion al boilerplate:
  FormRequest → toDto() → Controller → Action → Model/Repository → Event::dispatch()
```

**Ejemplo concreto:**

```php
// app/Http/Requests/Order/StoreOrderRequest.php
final class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:event,event_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:product,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'promo_code' => ['nullable', 'string'],
        ];
    }

    public function toDto(): CreateOrderDto
    {
        return new CreateOrderDto(
            eventId: (int) $this->validated('event_id'),
            items: collect($this->validated('items'))->map(
                fn (array $item) => new OrderItemDto(
                    productId: (int) $item['product_id'],
                    quantity: (int) $item['quantity'],
                )
            )->all(),
            promoCode: $this->validated('promo_code'),
            attendeeUserId: (int) $this->user()->user_id,
        );
    }
}

// app/Actions/Order/CreateOrder.php
final class CreateOrder
{
    public function __construct(
        private readonly StockManager $stockManager,
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function execute(CreateOrderDto $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {
            $products = Product::query()
                ->whereIn('product_id', array_map(fn ($i) => $i->productId, $dto->items))
                ->get()
                ->keyBy('product_id');

            $this->stockManager->reserve($products, $dto->items);

            $order = Order::create([
                'event_id' => $dto->eventId,
                'attendee_user_id' => $dto->attendeeUserId,
                'status' => OrderStatus::RESERVED,
                'reservation_expires_at' => now()->addMinutes(10),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'fee_total' => 0,
                'total' => 0,
                'currency' => 'EUR',
            ]);

            foreach ($dto->items as $itemDto) {
                $product = $products->get($itemDto->productId);
                $orderItem = $this->priceCalculator->calculateForOrder($order, $product, $itemDto->quantity);
                $order->items()->save($orderItem);
            }

            $order->recalculateTotals();
            $order->save();

            event(new OrderCreated($order));

            return $order;
        });
    }
}

// app/Http/Controllers/Order/OrderController.php
final class OrderController extends Controller
{
    public function __construct(
        private readonly CreateOrder $createOrder,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrder->execute($request->toDto());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
```

### 4.3 Convenciones de nomenclatura adaptadas

| Convencion DDD | Convencion boilerplate |
|---|---|
| `OrderRepository` (interface) | `App\Repositories\Order\Contracts\OrderRepository` |
| `EloquentOrderRepository` (impl) | `App\Repositories\Order\EloquentOrderRepository` |
| `CreateOrderCommand` | `App\DataTransferObjects\Order\CreateOrderDto` |
| `CreateOrderHandler` | `App\Actions\Order\CreateOrder` |
| `OrderConfirmed` (domain event) | `App\Events\Order\OrderConfirmed` |
| `OrderPolicy` | `App\Policies\OrderPolicy` |
| `Money` (value object) | `App\ValueObjects\Money` |
| `OrderStatus` (enum) | `App\Enums\OrderStatus` |

### 4.4 Convenciones de tabla adaptadas

Siguiendo las reglas del boilerplate:

```sql
-- Tablas en singular
-- PK como {model}_id
-- FK como {model}_id
-- SoftDeletes siempre

CREATE TABLE event (
    event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizer_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    visibility VARCHAR(30) NOT NULL DEFAULT 'public',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    venue_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    capacity INT NULL,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_event_status (status),
    INDEX idx_event_organizer (organizer_id),
    INDEX idx_event_slug (slug),
    FOREIGN KEY (organizer_id) REFERENCES organizer(organizer_id),
    FOREIGN KEY (venue_id) REFERENCES venue(venue_id),
    FOREIGN KEY (category_id) REFERENCES category(category_id)
);

CREATE TABLE product (
    product_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'ticket',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    start_sale_date DATETIME NULL,
    end_sale_date DATETIME NULL,
    min_per_order INT NOT NULL DEFAULT 1,
    max_per_order INT NULL,
    total_quantity INT NULL,
    sold_quantity INT NOT NULL DEFAULT 0,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_product_event (event_id),
    FOREIGN KEY (event_id) REFERENCES event(event_id)
);

CREATE TABLE `order` (
    order_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    event_id BIGINT UNSIGNED NOT NULL,
    attendee_user_id BIGINT UNSIGNED NULL,
    organizer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    reservation_expires_at DATETIME NULL,
    subtotal INT NOT NULL DEFAULT 0,
    tax_total INT NOT NULL DEFAULT 0,
    discount_total INT NOT NULL DEFAULT 0,
    fee_total INT NOT NULL DEFAULT 0,
    total INT NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    payment_id BIGINT UNSIGNED NULL,
    promo_code_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_order_event (event_id),
    INDEX idx_order_status (status),
    INDEX idx_order_attendee (attendee_user_id),
    FOREIGN KEY (event_id) REFERENCES event(event_id),
    FOREIGN KEY (attendee_user_id) REFERENCES user(user_id),
    FOREIGN KEY (organizer_id) REFERENCES organizer(organizer_id)
);
```

---

## 5. Paquetes a instalar (por fase)

### Fase 1: Fundacion

| Paquete | Proposito | Comando |
|---|---|---|
| `laravel/sanctum` | API auth (SPA + tokens) | `vendor/bin/sail composer require laravel/sanctum` |
| `spatie/laravel-permission` | Roles y permisos | `vendor/bin/sail composer require spatie/laravel-permission` |
| `spatie/laravel-sluggable` | Slugs automaticos | `vendor/bin/sail composer require spatie/laravel-sluggable` |
| `spatie/laravel-query-builder` | Filtros API | `vendor/bin/sail composer require spatie/laravel-query-builder` |

### Fase 2: Ticketing y Compra

| Paquete | Proposito | Comando |
|---|---|---|
| `stripe/stripe-php` | SDK Stripe | `vendor/bin/sail composer require stripe/stripe-php` |
| `laravel/cashier` (opcional) | Wrapper Stripe | `vendor/bin/sail composer require laravel/cashier` |
| `bacon/bacon-qr-code` | Generacion QR | `vendor/bin/sail composer require bacon/bacon-qr-code` |
| `barryvdh/laravel-dompdf` | Generacion PDF | `vendor/bin/sail composer require barryvdh/laravel-dompdf` |

### Fase 3-4: Operacion y Monetizacion

| Paquete | Proposito | Comando |
|---|---|---|
| `maatwebsite/excel` | Export CSV/XLSX | `vendor/bin/sail composer require maatwebsite/excel` |
| `laravel/horizon` | Monitor de colas Redis | `vendor/bin/sail composer require laravel/horizon` |

### Fase 5: Discovery

| Paquete | Proposito | Comando |
|---|---|---|
| `laravel/scout` | Abstraccion de busqueda | `vendor/bin/sail composer require laravel/scout` |

Meilisearch ya esta en `compose.yaml`. Scout se conecta a el.

### QA / Arquitectura (opcional)

| Paquete | Proposito | Comando |
|---|---|---|
| `qossmic/deptrac` | Validar dependencias entre capas | `vendor/bin/sail composer require --dev qossmic/deptrac` |

---

## 6. Ajustes al compose.yaml

El compose.yaml actual ya tiene lo esencial:

| Servicio | Estado | Nota |
|---|---|---|
| `laravel.test` (PHP 8.4) | OK | — |
| `mariadb` 11 | OK | Usar en vez de PostgreSQL. JSON en vez de JSONB. |
| `redis` | OK | Colas, cache, sesiones, locks de stock |
| `mailpit` | OK | Email testing en desarrollo |
| `minio` | OK | S3-compatible para uploads (imagenes, PDFs) |
| `meilisearch` | OK | Busqueda full-text de eventos |
| `sonarqube` | OK | Quality gate |

**Cambio recomendado:** SonarQube y su DB usan el puerto 9000, que colisiona con MinIO console (8900). Verificar que no haya conflicto.

---

## 7. Ajustes al flujo SDD/OpenSpec

El boilerplate usa OpenSpec/SDD para desarrollo guiado por especificaciones. La propuesta DDD se integra asi:

```
1. sdd-explore  → Explorar un bounded context (ej. "Ordering")
2. sdd-propose  → Proponer cambio dentro de un contexto
3. sdd-spec     → Especificar con Given/When/Then
4. sdd-design   → Diseno tecnico (secuencia de Action → Model → Event)
5. sdd-tasks    → Desglosar en tareas (crear migration, model, action, request, controller, test)
6. sdd-apply    → Implementar con TDD (Pest)
7. sdd-verify   → Verificar (pest + phpstan + pint)
8. sdd-archive  → Archivar cambio completado
```

---

## 8. Checklist de arranque

Antes de empezar la Fase 1, verificar:

- [ ] `composer setup` ejecutado correctamente
- [ ] `vendor/bin/sail up -d` levanta todos los servicios
- [ ] `composer qa` pasa limpio (rector + pint + phpstan + pest)
- [ ] `./sonar.sh` funciona sin errores
- [ ] MariaDB accesible desde el contenedor Laravel
- [ ] Redis operativo (CACHE_STORE=redis en .env)
- [ ] MinIO configurado como filesystem S3-compatible (FILESYSTEM_DISK=s3); el driver también permite S3 en producción
- [ ] Meilisearch accesible (SCOUT_DRIVER=meilisearch)
- [ ] Mailpit captura emails (MAIL_MAILER=smtp, MAIL_PORT=1025)

---

## 9. Resumen: que cambia y que se mantiene

### Se mantiene (convenciones del boilerplate)

- Estructura de carpetas por tipo (Actions, DTOs, Controllers, Models, etc.)
- Flujo FormRequest → DTO → Controller → Action
- Tablas en singular, PK `{model}_id`, SoftDeletes
- `declare(strict_types=1)` en todo
- Clases `final` por defecto
- Inyeccion de dependencias, no Facades

### Alcance de assets y paginacion

Sprint 5.4 habilita object storage S3-compatible con MinIO local y S3 en producción. No implementa una CDN real: la distribución mediante CDN queda diferida. Cursor pagination también queda diferida a un sprint futuro.
- Pest para testing
- QA pipeline: Rector → Pint → PHPStan → Tests → SonarQube
- OpenSpec/SDD workflow
- Sail para entorno local

### Se adapta (de la propuesta DDD)

- Bounded contexts → subcarpetas por dominio dentro de Actions/, Events/, etc.
- Domain Events → Laravel Events system (`event()`, Listeners)
- CQRS → Actions (escritura) + ViewModels/Resources (lectura)
- Repositories → opcionales, con Contracts/
- Value Objects → `app/ValueObjects/`
- Enums → `app/Enums/` (PHP 8.4 enums)
- Policies → `app/Policies/`
- Services → `app/Services/` (logica transversal: pricing, stock, payment gateway)

### Se anade (nuevo para el dominio de eventos)

- ~25 modelos Eloquent
- ~12 enums
- ~30 actions
- ~20 domain events + listeners
- ~10 repositories (opcionales)
- ~6 services transversales
- Integracion Stripe
- Generacion QR y PDF
- Busqueda con Meilisearch/Scout
- Roles y permisos con Spatie

---

## 10. Siguiente paso concreto

> **Referencia histórica:** Sprint 1.1 (Setup y Auth) completado y archivado. El estado actual posterior incluye Sprint 6.1 implementado; esta sección conserva la secuencia original y no prescribe reiniciar por Sprint 1.2.

1. Consultar el plan vigente antes de abrir un nuevo cambio OpenSpec.
2. Mantener el patron `FormRequest -> toDto() -> Controller -> Action` ya validado en el repositorio.
3. Usar este mapeo como referencia arquitectónica, no como checklist de trabajo pendiente.
