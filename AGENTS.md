# AGENTS.md

## 🚨 CONTEXTO CRÍTICO DEL REPOSITORIO

Este repositorio es un boilerplate de Laravel moderno y está configurado para seguir un flujo de trabajo de desarrollo guiado por especificaciones (SDD/TDD). La clave para trabajar aquí es entender que la calidad del código es un **requisito operacional**, no opcional.

### ⚙️ Flujo de Trabajo de Desarrollo (Workflow)

Todo cambio debe completarse en las siguientes fases estrictas:
1. **SDD Planning:** Definir la especificación de dominio (`sdd-spec`) o explorar requisitos (`sdd-explore`).
2. **Design:** Crear el diseño técnico (`sdd-design`).
3. **Tasks:** Desglosar en tareas de implementación (`sdd-tasks`).
4. **Implementation:** Ejecutar el código y proveer la cobertura de pruebas (`sdd-apply` / `go-testing`).
5. **Verification:** Pasar la verificación final(`sdd-verify`).
6. **Archive:** Archivar el cambio completado (`sdd-archive`).

**¡IMPORTANTE!** Nunca implementar código sin haber pasado por las etapas SDD/TDD.

### 💾 Setup y Dependencias (¡CRÍTICO!)

1. **Inicialización del Entorno:** La primera vez que se trabaja en el proyecto, el comando completo preferido es ejecutar `npm run dev`. Esto asegura la configuración de todos los servicios (servidor, colas, logs, assets, etc.).
2. **Composición:** Siempre utilizar `composer run <task>` para comandos de vendor (ej. `composer run pint --dirty`) en lugar de llamarlos directamente en el shell.

### 🛠️ Estándares de Calidad (Flujo de Trabajo Obligatorio)

Antes de cualquier commit de implementación, debe pasar el siguiente pipeline:
1. **Code Style:** `composer run pintar --dirty`
2. **Static Analysis:** `$ phpstan analyse`
3. **Testing:** `composer run test` (El test runner está configurado para usar Pest).

### 🧭 Patrones de Desarrollo (Generales)

*   **Commit Units:** Los commits deben ser unidades de trabajo aisladas (Feature, Fix, Chore), lo que se gestiona con el skill `work-unit-commits`.
*   **Archivos de verdad:** Las rutas y constantes de la aplicación están definidas en el directorio `config/`.

### 📚 Tools & Shortcuts

- **OpenSpec/SDD:** Use la tool `sdd-` para cualquier mejora funcional.
- **Documentación de Procesos:** Use el skill `cognitive-doc-design` para crear documentación que no sea código.
- **Consultar el proceso SDD:** Ejecutar `sdd-onboard` para recordatorios del workflow.
- **Mapa de documentación:** ver [`docs/README.md`](docs/README.md) para el índice completo (estado, producto, arquitectura, UX/UI, librerías).

===

<laravel-boost-guidelines>
=== .ai/architecture rules ===

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

=== .ai/code-style rules ===

# Estilo de Código

## Idioma

- Todo el código en inglés (clases, métodos, variables, migraciones, rutas, comentarios)
- Comentarios de migraciones en español si son necesarios
- Commits en inglés (ver skill conventional-commits)

## PHP

- `declare(strict_types=1)` en cada archivo PHP
- Type hints en parámetros y retorno siempre
- Readonly properties donde aplique
- Clases `final` por defecto salvo que se necesite extender
- Early return, evitar else
- Enums para valores fijos (estados, tipos)
- No abreviar nombres: `$shortUrl` no `$su`, `calculateDistance` no `calcDist`
- Máximo un nivel de indentación por método (extraer a métodos privados si crece)

## Laravel

- Inyección de dependencias, nunca Facades
- Config vía config(), nunca env() fuera de config/
- Rutas con nombres: ->name('short-urls.store')
- Migraciones descriptivas: create_short_url_table, add_expires_at_to_short_url_table

## Formato

- Pint con preset Laravel se encarga del formato
- No formatear manualmente, ejecutar pint

=== .ai/forbidden rules ===

# Prohibiciones

## Seguridad crítica

- NUNCA leer, mostrar ni acceder al archivo .env (usar .env.example como referencia)
- NUNCA ejecutar git push, merge, rebase ni reset sin aprobación explícita
- git add y git commit están permitidos tras pasar el QA pipeline completo
- NUNCA ejecutar migraciones destructivas sin aprobación explícita
- NUNCA exponer credenciales, tokens ni secrets en código o logs

## Código

- No instalar paquetes sin mi aprobación explícita
- No usar dd(), dump(), var_dump() ni ray() sin mi aprobación explícita
- No usar env() fuera de archivos de config
- No modificar archivos de configuración sin justificación
- No usar query raw SQL sin justificación
- No usar Facades cuando se puede inyectar
- No usar Route::model() implícito, siempre explicit binding o resolución en Actions
- No retornar arrays desde controllers, siempre Resources o JsonResponse
- No usar mass assignment sin $fillable explícito
- No suprimir errores con @ ni try/catch vacíos
- No dejar imports sin usar

=== .ai/qa rules ===

# QA Pipeline

## Flujo antes de cada commit

1. `composer qa` (rector → pint → phpstan → tests)
2. `./sonar.sh` (desde el host, no desde Sail)
3. Corregir issues y repetir hasta quedar limpio
4. Commit

## Reglas

- No se hace commit si algún paso falla
- Si Rector detecta cambios, aplicarlos con `composer rector` y volver a ejecutar qa
- Después de qa, ejecutar Sonar y corregir issues hasta que quede aceptable

## Scripts individuales

- `composer rector` — aplicar correcciones de Rector
- `composer pint` — formatear código
- `composer phpstan` — análisis estático
- `composer test` — ejecutar tests

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- livewire/volt (VOLT) - v1
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== spatie/laravel-activitylog rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method | Description |
|--------|-------------|
| `logFillable()` | Log all fillable attributes |
| `logAll()` | Log all attributes |
| `logOnly(array)` | Log specific attributes |
| `logExcept(array)` | Exclude attributes |
| `logOnlyDirty()` | Only log changed attributes |
| `dontLogEmptyChanges()` | Skip logging when no tracked attributes changed |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)` | Set custom log name |
| `setDescriptionForEvent(Closure)` | Custom description per event |
| `useAttributeRawValues(array)` | Store raw (uncast) values |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:
- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

</laravel-boost-guidelines>
