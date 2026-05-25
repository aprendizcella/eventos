# Arquitectura del Proyecto

## Dos flujos distintos

### Acción (el usuario hace algo → escritura)

Request → FormRequest (valida) → toDto() → Controller → Action(__invoke) → 201/204

### Presentación (el usuario pide datos → lectura)

Request → Controller → ViewModel → Response (el ViewModel usa Resources internamente)

Para respuestas simples de un solo tipo de dato, el Controller puede usar un Resource directamente sin ViewModel.

## Piezas del sistema

### Actions

Ubicación: `app/Actions/{Dominio}/`
- Toda lógica de negocio de escritura va aquí
- Una acción = una responsabilidad
- Recibe un DTO, retorna modelo/colección/primitivo (nunca Response)

### DTOs

Ubicación: `app/DataTransferObjects/{Dominio}/`
- Transportan datos entre capas
- Sin lógica

### FormRequests

Ubicación: `app/Http/Requests/`
- Validan entrada HTTP
- Construyen el DTO vía `toDto()`
- El Controller nunca accede a `validated()` directamente

### Controllers

Ubicación: `app/Http/Controllers/{Dominio}/`
- Thin: solo coordinan, nunca contienen lógica de negocio
- Invocables (`__invoke()`) para acciones que no son CRUD
- Resource (index, store, show, update, destroy) para CRUD
- Máximo 5 métodos, si crece se divide

### Resources

Ubicación: `app/Http/Resources/`
- Formatean la representación JSON de un modelo
- Nunca retornar modelos o arrays directamente desde Controllers

### ViewModels

Ubicación: `app/ViewModels/{Dominio}/`
Clase base: `app/ViewModels/ViewModel.php`
- Capa de presentación: preparar datos de lectura para el cliente
- Usar cuando un endpoint necesita devolver múltiples tipos de datos
- Pueden usar Resources internamente para formatear

### Models

Ubicación: `app/Models/`
- Tabla en singular, PK como `{model}_id`, FK como `{model}_id`, SoftDeletes siempre
- Cada modelo va acompañado de factory y migration

### Migrations

- Tabla en singular, SoftDeletes siempre en tablas nuevas
- `down()` siempre implementado

### Repositories (opcional)

Ubicación: `app/Repositories/{Dominio}/`
Interfaces: `app/Repositories/{Dominio}/Contracts/`
- Usar cuando una Action necesita abstraer el acceso a datos
- Las Actions dependen de la interfaz, no de la implementación
- No obligatorio: si la Action es simple, puede usar el modelo directamente

### Services

Ubicación: `app/Services/`
- Solo para lógica compleja reutilizable entre múltiples Actions
- Si dudas entre Action y Service, usa Action
