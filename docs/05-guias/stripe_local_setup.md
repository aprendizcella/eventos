# Guía de Configuración de Stripe (Test Mode) en Local

Esta guía describe los pasos necesarios para configurar y probar la integración de pagos de **Stripe** en un entorno de desarrollo local (Local/Testing Mode) de la forma más cercana posible al entorno de producción.

---

## 1. Cuenta de Pruebas de Stripe (Developers)

Para interactuar con la pasarela sin utilizar dinero real, Stripe proporciona claves especiales de pruebas.

1. Regístrate o inicia sesión en el [Stripe Dashboard](https://dashboard.stripe.com/).
2. Activa el interruptor **"Modo de prueba" (Test Mode)** en la esquina superior derecha del panel.
3. Dirígete a la pestaña **Desarrolladores** -> **Claves de API**.
4. Copia tus dos credenciales:
   * **Publishable key:** Empieza por `pk_test_...`
   * **Secret key:** Empieza por `sk_test_...`

---

## 2. Configuración en la Aplicación Laravel

Añade las claves de prueba que copiaste al archivo `.env` de tu proyecto local:

```env
STRIPE_KEY=pk_test_tu_clave_publica_aqui
STRIPE_SECRET=sk_test_tu_clave_secreta_aqui
```

> [!NOTE]
> La aplicación lee estas variables mediante `config/services.php` bajo la clave `'stripe'`, cumpliendo con la regla de diseño de no acceder a `env()` fuera del directorio de configuración.

---

## 3. Configuración y Túnel de Webhooks (Stripe CLI)

Los webhooks asíncronos de Stripe (como la confirmación del pago `payment_intent.succeeded`) se envían desde los servidores de Stripe a tu máquina local. Para lograr esto sin exponer tu puerto local a internet de forma insegura, utilizamos la **Stripe CLI**.

### Paso A: Instalar Stripe CLI
* **macOS (con Homebrew):**
  ```bash
  brew install stripe/stripe-cli/stripe
  ```
* **Windows (con Scoop):**
  ```powershell
  scoop bucket add stripe https://github.com/stripe/stripe-cli.git
  scoop install stripe
  ```
* **Otros sistemas:** Consulta la documentación oficial de [Stripe CLI Installation](https://docs.stripe.com/stripe-cli).

### Paso B: Iniciar Sesión
Autentica tu máquina local con tu cuenta de Stripe ejecutando en tu terminal:
```bash
stripe login
```
Sigue el enlace generado en el navegador para autorizar la conexión.

### Paso C: Abrir el túnel de Webhooks
Inicia el reenvío de eventos del webhook hacia tu servidor local ejecutando el comando:
```bash
stripe listen --forward-to http://localhost/api/v1/webhooks/stripe
```
La consola te mostrará una salida parecida a la siguiente:
> `Ready! Your webhook signing secret is whsec_xyz123...`

### Paso D: Configurar el secreto de firma
Copia el secreto de firma (`whsec_...`) e insértalo en tu archivo `.env`:
```env
STRIPE_WEBHOOK_SECRET=whsec_xyz123...
```
Mantén este comando ejecutándose en segundo plano en una pestaña de tu terminal siempre que desees probar flujos de pago.

---

## 4. Pruebas y Simulación de Eventos

Una vez configurado todo el entorno local, puedes validar el funcionamiento completo de forma manual:

1. Levanta tu servidor local de Laravel Sail:
   ```bash
   vendor/bin/sail up -d
   vendor/bin/sail npm run dev
   ```
2. Ejecuta un evento de prueba en Stripe CLI desde una pestaña nueva:
   ```bash
   stripe trigger payment_intent.succeeded
   ```
3. Verifica la salida en la terminal de `stripe listen`. Deberías ver un código de respuesta HTTP `200` procesado de la siguiente manera:
   ```text
   2026-07-01 09:00:00  [200] POST http://localhost/api/v1/webhooks/stripe
   ```
4. Comprueba que el evento se ha registrado correctamente en la base de datos local de tu aplicación consultando la tabla `processed_webhook_event`.

---

## 5. Decisión de Arquitectura y Simulador Local

Para facilitar las pruebas continuas de desarrollo sin necesidad de credenciales reales ni conexión constante a internet, se ha tomado la decisión arquitectónica (**A14**) de:
1.  **Mantener el backend 100% acoplado a Stripe** (firma de webhook, acciones de pago, persistencia de reintentos, redondeos).
2.  **Aplazar la carga de Stripe Elements (Frontend) a Staging/Producción**, evitando dependencias de scripts externos y requerimientos HTTPS en desarrollo local.
3.  **Proveer dos simuladores interactivos** en el Paso 3 del Checkout en entornos `local`/`testing`:
    *   **Simulate Simple Payment (Offline):** Confirma directamente la orden localmente sin tocar el flujo de Stripe.
    *   **Simulate Stripe Webhook Payment (Simulador Completo):** Ejecuta la acción `InitiatePaymentAction` y luego construye un payload firmado con HMAC simulando la llamada real de Stripe a nuestro webhook, procesando todo el ciclo de transaccionalidad e idempotencia.

