# Proposal: Sprint 3.4 — Panel de Evento Completo

## Intent

Completar la capa operativa del evento con un dashboard de KPIs, settings avanzados y API de operación para que el organizer pueda gestionar el evento sin salir del panel.

## Scope

### In Scope
- Dashboard con KPIs, ventas y gráfico de ventas diarias.
- Settings del evento con preferencias operativas y plantillas de notificación.
- API `/attendees`, `/check-in` y `/messages` para operación interna.
- Tests de dashboard, settings y API.

### Out of Scope
- Facturación, reembolsos y comisiones.
- Nuevas features de discovery público.
- Automatizaciones de marketing fuera de operación básica.

## Approach

Seguir el patrón del repo: ViewModel para agregaciones, Volt/Blade para presentación, FormRequests para validación y authorization, Policies para permisos y controller fino para la API.

## Success Criteria

- [x] Dashboard muestra KPIs y ventas.
- [x] Settings de evento configurables.
- [x] API de operación disponible y protegida.
- [x] QA pipeline pasa limpio.
