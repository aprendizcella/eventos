# Design: Sprint 3.4 — Panel de Evento Completo

## Architecture

- `EventKpiViewModel` concentra los cálculos del dashboard para mantener la vista liviana.
- `EventSettingsRequest` valida la configuración del evento.
- `ApiCheckInRequest` y `SendBulkMessageRequest` aíslan la validación de la API.
- `EventApiController` mantiene la capa HTTP delgada y delega en Actions existentes.
- `NotificationTemplatePolicy` reutiliza la autorización del evento para mantener consistencia.

## Key Decisions

- Las métricas de dashboard no viven en Volt.
- La API responde con errores de dominio cuando corresponde y no mezcla lógica de negocio en el controlador.
- `notification_template` se modela como entidad propia para evitar campos opacos en JSON.

## Validation Strategy

- Tests de render para el dashboard.
- Tests de request/authorization para settings y API.
- QA completo con Pint, PHPStan y Pest.
