# Especificación técnica base del boilerplate de eventos

**Proyecto:** Boilerplate Laravel para plataforma de eventos, ticketing y reservas  
**Referencia funcional y arquitectónica:** Hi.Events  
**Fecha:** 22/06/2026  
**Estado:** Base técnica inicial implementada (Sprint 1.1 completado)

> **Estado de ejecución (post Sprint 1.1):** La base técnica del boilerplate está operativa sobre Laravel 12 / PHP 8.4 / Sail. El módulo de autenticación y usuarios (sección 7.1) está implementado: registro, login, logout, reset de password, verificación de email (vía `MustVerifyEmail`), roles y permisos con Spatie Permission, y trazabilidad con Spatie Activitylog. Stack de Fase 1 instalado: Sanctum, Spatie Permission, Spatie Activitylog, Mews/Purifier, Livewire + Volt. Tests en verde y QA pipeline (`composer qa`) limpio. Los módulos 7.2–7.7 siguen como propuesta pendiente de implementar en los sprints siguientes.

## 1. Objetivo del documento

Este documento define la base técnica y funcional sobre la que se desarrollará el boilerplate de gestión de eventos.
Su finalidad es servir como punto de partida para construir una plataforma escalable, mantenible y coherente con una arquitectura moderna en Laravel.

La referencia principal de producto y arquitectura será **Hi.Events**, pero la implementación se adaptará al boilerplate existente y a sus convenciones actuales.

## 2. Propósito del sistema

La plataforma debe permitir:

- Publicar y gestionar eventos.
- Vender entradas online.
- Gestionar asistentes y check-in.
- Administrar contenido público y páginas informativas.
- Preparar el sistema para evolucionar hacia un SaaS multi-tenant.

El sistema se concibe como una base reutilizable, no como una solución cerrada para un único caso de uso.

## 3. Referencias de diseño

### 3.1. Hi.Events como referencia

Hi.Events se toma como referencia por:

- su enfoque de plataforma SaaS de eventos;
- su separación clara entre backend, frontend y dominio;
- el uso de capas diferenciadas para mantener la lógica aislada;
- su orientación a escalabilidad y evolución funcional.

### 3.2. Boilplate actual

La estructura visible del proyecto incluye, entre otras, estas áreas iniciales:

- `Users`
- `Events`
- `Categories`
- `Pages`
- `Settings`
- `Banners`
- `HOME Page Asistente`

Estas áreas sirven como base del producto y deben consolidarse antes de ampliar el dominio.

## 4. Principios arquitectónicos

### 4.1. Principios guía

1. **Separación estricta de responsabilidades**
2. **Lógica de negocio fuera de controladores**
3. **Validación explícita de entradas**
4. **DTOs para transporte de datos**
5. **Servicios y acciones pequeñas, con una responsabilidad**
6. **Código legible y consistente**
7. **Preparación para crecimiento funcional e internacional**

### 4.2. Reglas de diseño

- Las operaciones de escritura deben seguir un flujo claro de validación y ejecución.
- La lectura debe devolver datos estructurados y preparados para presentación.
- La lógica compleja debe quedar aislada en clases de dominio o servicios específicos.
- La persistencia no debe contaminar la capa de presentación.

## 5. Alcance funcional inicial

### 5.1. Perfil asistente

El asistente podrá:

- descubrir eventos;
- buscar por categoría, ciudad, fecha o texto libre;
- consultar detalle de evento;
- comprar entradas;
- recibir confirmaciones;
- acceder a sus tickets digitales;
- revisar historial de compras.

### 5.2. Perfil organizador

El organizador podrá:

- crear y editar eventos;
- definir tipos de entradas;
- publicar páginas de evento;
- consultar ventas y asistentes;
- gestionar aforo;
- hacer check-in;
- revisar métricas básicas.

### 5.3. Administración interna

La plataforma deberá contemplar:

- gestión de usuarios;
- gestión de contenidos públicos;
- configuración general;
- control de visibilidad de eventos y páginas;
- soporte para futuras reglas de monetización.

## 6. Arquitectura funcional objetivo

### 6.1. Capas principales

| Capa | Responsabilidad |
|---|---|
| Frontend público | Descubrimiento, compra y acceso del asistente |
| Frontend organizador | Gestión operativa de eventos y ventas |
| Backend | Reglas de negocio, persistencia y orquestación |
| Dominio | Casos de uso y lógica central |
| Infraestructura | Integraciones, pagos, notificaciones, exportaciones |

### 6.2. Flujo general recomendado

**Lectura**

`Request → Controller → ViewModel/Resource → Response`

**Escritura**

`Request → FormRequest → DTO → Controller → Action/Handler → Domain Service → Repository`

### 6.3. Relación con Hi.Events

El boilerplate debe inspirarse en Hi.Events en lo siguiente:

- separación de dominio y persistencia;
- controladores delgados;
- uso de objetos de transporte;
- validación antes de ejecutar casos de uso;
- modularidad por áreas funcionales.

## 7. Módulos funcionales propuestos

### 7.1. Módulo de autenticación y usuarios

> **Estado: IMPLEMENTADO (Sprint 1.1).** Registro, login, logout, reset de password y verificación de email operativos; roles y permisos vía Spatie Permission; trazabilidad vía Spatie Activitylog.

Responsable de:

- registro;
- inicio de sesión;
- recuperación de contraseña;
- gestión de perfil;
- roles y permisos básicos.

### 7.2. Módulo de eventos

Responsable de:

- crear evento;
- editar evento;
- publicar/despublicar;
- definir fechas, localización y capacidad;
- asociar imágenes y contenido.

### 7.3. Módulo de tickets

Responsable de:

- definir tipos de entrada;
- precio;
- cupos;
- restricciones;
- disponibilidad;
- generación del ticket digital.

### 7.4. Módulo de compra

Responsable de:

- selección de entradas;
- checkout;
- confirmación;
- reserva temporal;
- gestión de estado de pedido.

### 7.5. Módulo de asistentes

Responsable de:

- listado de asistentes;
- datos de contacto;
- exportación;
- trazabilidad de compras;
- check-in.

### 7.6. Módulo de contenido y páginas

Responsable de:

- páginas informativas;
- home;
- banners;
- bloques de contenido;
- SEO básico.

### 7.7. Módulo de configuración

Responsable de:

- parámetros globales;
- branding;
- moneda;
- idioma;
- correo;
- reglas del sistema.

## 8. Modelo de dominio inicial

### 8.1. Entidades principales

- `User`
- `Role`
- `Event`
- `Category`
- `TicketType`
- `Order`
- `OrderItem`
- `Attendee`
- `Page`
- `Banner`
- `Setting`

### 8.2. Relación conceptual

```text
User
 ├── manages → Event
 ├── owns → Page
 └── buys → Order

Event
 ├── belongs to → Category
 ├── contains → TicketType
 └── generates → Attendee / OrderItem
```

### 8.3. Reglas de negocio iniciales

- Un evento no puede publicarse sin información mínima.
- Una entrada no puede venderse si no tiene capacidad o precio válido.
- Un pedido debe tener estado trazable.
- El check-in debe registrar quién validó y cuándo.

## 9. Casos de uso prioritarios

### 9.1. Asistente

1. Buscar evento.
2. Ver detalle.
3. Seleccionar entradas.
4. Completar compra.
5. Recibir confirmación.
6. Acceder al ticket.

### 9.2. Organizador

1. Crear evento.
2. Configurar entradas.
3. Publicar evento.
4. Ver ventas.
5. Ver asistentes.
6. Validar acceso.

## 10. Requisitos no funcionales

### 10.1. Seguridad

- validación estricta de formularios;
- protección CSRF;
- control de acceso por rol;
- no exponer datos sensibles en respuestas;
- trazabilidad de acciones relevantes.

### 10.2. Escalabilidad

- estructura preparada para multi-tenant futuro;
- separación de lectura y escritura;
- diseño apto para colas, caché y tareas asíncronas;
- crecimiento internacional por idiomas y zonas horarias.

### 10.3. Mantenibilidad

- código modular;
- nombres consistentes;
- lógica de negocio localizada;
- tests por capa o por caso de uso.

### 10.4. Observabilidad

- logs útiles;
- errores controlados;
- eventos de dominio cuando aporten trazabilidad;
- métricas básicas de conversión y ventas.

## 11. Roadmap de implementación

### Fase 1 — MVP técnico y funcional

> **Parcialmente implementado:** autenticación básica completada en Sprint 1.1. Catálogo, detalle público, compra y gestión de pedidos pendientes.

- autenticación básica;
- catálogo de eventos;
- detalle público del evento;
- compra simple;
- gestión de pedidos;
- base administrativa mínima.

### Fase 2 — Operación del organizador

- panel de organización;
- configuración avanzada de eventos;
- tipos de entradas;
- asistentes;
- exportaciones;
- check-in.

### Fase 3 — Escalado de producto

- promociones;
- automatizaciones;
- analítica;
- internacionalización;
- integraciones externas.

### Fase 4 — SaaS avanzado

- multi-tenant real;
- facturación;
- planes de suscripción;
- permisos por organización;
- branding por cliente.

## 12. Fuera de alcance inicial

No se abordará en la primera iteración:

- marketplace avanzado;
- recomendación por IA;
- app móvil nativa;
- multi-tenant completo;
- facturación compleja;
- integraciones múltiples de pago;
- motor de marketing avanzado.

## 13. Criterios de aceptación del documento base

Este documento se considerará suficiente como base inicial si:

- define el alcance funcional principal;
- fija la arquitectura de referencia;
- identifica los módulos básicos;
- diferencia asistente y organizador;
- permite empezar a diseñar entidades, casos de uso y migraciones.

## 14. Próximo paso recomendado

El siguiente paso es convertir esta especificación en un **desglose técnico por módulos**, con:

- entidades;
- relaciones;
- migraciones;
- acciones;
- requests;
- controladores;
- vistas;
- pruebas.

