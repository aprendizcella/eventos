# Análisis Arquitectónico — Post Sprint 3.2

> **Fecha de análisis:** 2026-07-02
> **Sprint de referencia:** 3.2 (Waitlist completado)
> **Sprint siguiente:** 3.3 (Exportación y Mensajería Masiva)
> **Estado del pipeline QA:** ✅ 531 tests, PHPStan OK, Pint OK

Este documento recoge el análisis de arquitectura y código generado al terminar el Sprint 3.2, antes de iniciar el Sprint 3.3. Su objetivo es dejar constancia de la deuda técnica identificada, con la prioridad de cada ítem y la acción recomendada.

---

## 1. Estado General de la Arquitectura

### ✅ Puntos fuertes confirmados

| Área | Observación |
|---|---|
| **Separación de capas** | Actions, Services, Models y DTOs bien delimitados. Controllers y Volt components son thin. |
| **Concurrencia** | Todo lo crítico usa `DB::transaction` + `lockForUpdate`. Los eventos de dominio se disparan con `DB::afterCommit`. |
| **Idempotencia** | `GenerateAttendeesAction`, `ConvertWaitlistEntryAction` y `HandleStripeWebhookAction` implementan idempotencia correctamente. |
| **Modelos** | PHPDoc completo, casts en `casts()`, relaciones con genéricos. Calidad uniforme en los 17 modelos. |
| **DTOs** | Inmutables (`readonly`), sin lógica, con type hints completos. |
| **Estilo de código** | `declare(strict_types=1)`, clases `final`, early return, sin abreviaciones. Consistente en todo el proyecto. |

---

## 2. Deuda Técnica Identificada

### 🔴 ALTA — Refactorizar antes del Sprint 3.3

#### 2.1 Lógica de expiración de órdenes sin Action propia

**Archivo afectado:** `app/Console/Commands/ReleaseExpiredReservations.php`

**Problema:** El comando Artisan contiene lógica de negocio inline (transacción + `update` de la orden + llamada al rollback). No existe ninguna `ExpireTicketOrderAction` que encapsule esto. Contrasta directamente con `CancelTicketOrderAction`, que sí está correctamente extraída.

```php
// Situación actual — lógica inline en el command
public function handle(RollbackWaitlistReservationAction $rollbackAction): int {
    foreach ($expiredOrders as $order) {
        DB::transaction(function () use ($order, $rollbackAction) {
            $order->update(['status' => TicketOrderStatus::Expired, ...]);
            if ($order->waitlist_entry_id !== null) {
                $rollbackAction($order->waitlistEntry);
            }
            activity()-> ... ->log('expired');
        });
    }
}
```

**Riesgo:** Si en el Sprint 3.3/3.4 otro flujo necesita expirar una orden (p.ej. desde un webhook o un panel de admin), habría que duplicar esta lógica o reutilizar el command directamente, lo cual no es aceptable.

**Acción recomendada:** Extraer a `app/Actions/Orders/ExpireTicketOrderAction.php` y hacer que el command solo itere y delegue.

---

#### 2.2 Lógica de negocio directamente en un componente Volt (UI)

**Archivo afectado:** `resources/views/livewire/organizers/events/waitlist-management.blade.php`

**Problema:** El método `expire()` del componente aplica directamente `$entry->update()` y dispara `event(new WaitlistEntryExpired(...))` sin pasar por ninguna Action. Existe `ExpireWaitlistEntriesAction` para la expiración programada, pero no se usa en el path manual desde la UI.

```php
// Situación actual — negocio en el componente Volt
public function expire(int $entryId): void {
    $entry = WaitlistEntry::query()->findOrFail($entryId);
    $entry->update([
        'status' => WaitlistStatus::Expired,
        'token' => null,
        'expires_at' => null,
    ]);
    event(new WaitlistEntryExpired(...)); // afterCommit no garantizado aquí
}
```

**Riesgo:** La expiración manual desde la UI y la expiración automática del scheduler pueden divergir si se añaden nuevos campos. Además, el evento se dispara sin `DB::afterCommit`, lo que podría notificar al siguiente en cola antes de que el cambio quede persistido.

**Acción recomendada:** Crear `app/Actions/Waitlist/ManualExpireWaitlistEntryAction.php` que use `DB::transaction` + `DB::afterCommit` correctamente, y delegar desde el componente Volt.

---

### 🟡 MEDIA — Abordar antes de la Fase 4

#### 2.3 `resolve()` en lugar de inyección de dependencias

**Archivo afectado:** `app/Actions/Payments/HandleStripeWebhookAction.php` — línea 82

**Problema:** Se usa `resolve(ConfirmTicketOrderAction::class)->__invoke(...)` en lugar de inyectar `ConfirmTicketOrderAction` en el constructor. Viola la regla explícita del proyecto: _"Inyección de dependencias, nunca Facades"_.

```php
// Situación actual
resolve(ConfirmTicketOrderAction::class)->__invoke($payment->ticketOrder);

// Correcto sería
public function __construct(
    private ConfirmTicketOrderAction $confirmTicketOrderAction,
) {}
// y luego:
($this->confirmTicketOrderAction)($payment->ticketOrder);
```

---

#### 2.4 Precios almacenados como `float` — riesgo en Fase 4

**Archivos afectados:** `StockManager.php`, `PriceCalculator.php`, `ReserveStockDto.php`, `TicketOrder.php`, `TicketOrderItem.php`

**Problema:** Todos los precios (subtotal, discount, total, price) son `float`. Los cálculos acumulativos con `float` son susceptibles a errores de precisión (`0.1 + 0.2 !== 0.3`). Esto es manejable en ticketing básico, pero se vuelve crítico en la Fase 4 cuando se añadan facturas, comisiones y payouts.

**Acción recomendada para Fase 4:** Migrar a enteros en céntimos (igual que Stripe internamente) o usar `bcmath` para aritmética exacta. Requiere migración de datos y refactor de la capa de precios.

> **⚠️ Nota:** Este cambio tiene alto impacto en base de datos y en la UI (formato de moneda). Debe planificarse como primer task del Sprint 4.1a, antes de añadir facturas.

---

### 🟢 BAJA — Mejoras de calidad menores (cualquier PR)

#### 2.5 `@property` faltante en `TicketOrder`

**Archivo afectado:** `app/Models/TicketOrder.php`

`waitlist_entry_id` aparece en `$fillable` y en `casts()` pero no está documentado en el bloque `@property` del modelo. PHPStan puede no inferir el tipo correctamente en accesos directos.

---

#### 2.6 FQCN inline en `StockManager::getAvailableCapacity()`

**Archivo afectado:** `app/Services/StockManager.php` — líneas 43-44

Los enums `WaitlistStatus::Notified` y `WaitlistStatus::Reserved` se referencian con el namespace completo inline (`\App\Enums\WaitlistStatus::...`) mientras el resto del archivo usa imports estándar. Inconsistencia menor de estilo.

---

#### 2.7 `ReleaseExpiredReservations` command no es `final`

**Archivo afectado:** `app/Console/Commands/ReleaseExpiredReservations.php`

La convención del proyecto es `final` por defecto. El comando extiende `Command` de Laravel pero no tiene ninguna razón para no ser `final`.

---

## 3. Tabla de Prioridades

| # | Prioridad | Descripción | Archivo | Cuándo resolver |
|---|---|---|---|---|
| 2.1 | 🔴 Alta | Sin `ExpireTicketOrderAction` — lógica inline en command | `ReleaseExpiredReservations.php` | Antes Sprint 3.3 |
| 2.2 | 🔴 Alta | Negocio en Volt component (`expire()`) | `waitlist-management.blade.php` | Antes Sprint 3.3 |
| 2.3 | 🟡 Media | `resolve()` en lugar de DI | `HandleStripeWebhookAction.php:82` | Antes Fase 4 |
| 2.4 | 🟡 Media | `float` para precios — riesgo de precisión | StockManager, PriceCalculator, DTOs | Sprint 4.1 |
| 2.5 | 🟢 Baja | `@property` faltante en `TicketOrder` | `TicketOrder.php` | Cualquier PR |
| 2.6 | 🟢 Baja | FQCN inline inconsistente | `StockManager.php:43-44` | Cualquier PR |
| 2.7 | 🟢 Baja | Command no es `final` | `ReleaseExpiredReservations.php` | Cualquier PR |

---

## 4. Plan de Acción Inmediata

Antes de iniciar el Sprint 3.3, se recomienda un **micro-refactor** (estimación: 1-2 horas) que aborde únicamente los puntos 2.1 y 2.2:

### Pasos concretos

1. **Crear `app/Actions/Orders/ExpireTicketOrderAction.php`**
   - Mover la transacción de expiración desde el command a esta Action
   - La Action recibe un `TicketOrder` y lo expira atómicamente
   - El command solo itera las órdenes y delega

2. **Crear `app/Actions/Waitlist/ManualExpireWaitlistEntryAction.php`**
   - Encapsula `lockForUpdate`, `update(status => Expired)` y el evento en `DB::afterCommit`
   - El componente Volt llama a esta Action en lugar de tener lógica inline

3. **Actualizar tests** si los hay para estos flujos (no eliminar los existentes)

4. **Ejecutar `composer qa`** para verificar que todo sigue en verde

### Por qué hacerlo ahora

- No cambia ningún contrato público ni ruta expuesta
- Los tests existentes de waitlist y órdenes cubren los flujos afectados
- Solo mueve código a su lugar correcto, sin cambios de comportamiento
- Si se deja para después del Sprint 3.3, habrá más código dependiente y el refactor costará más

---

## 5. Conexión con Otros Documentos

- Ver `docs/01-producto/PLAN_IMPLEMENTACION.md` para el roadmap completo
- Ver `docs/00-estado/ESTADO_EJECUCION.md` para el estado actual de sprints
- Ver `docs/02-arquitectura/DECISIONES_ARQUITECTURA.md` para las decisiones de diseño base
