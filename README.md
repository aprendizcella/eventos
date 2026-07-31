# eventos

> Plataforma de gestión y descubrimiento de eventos — multitenant, multi-idioma, con AI-ready development workflow.
> Trabajo Fin de Máster — BIG School · [events.saboreateruel.com](https://events.saboreateruel.com/)

---

## 🔑 Demo credentials

| Rol | Email | Contraseña | Dominio |
|-----|-------|-----------|---------|
| Superadministrador | `test@example.com` | `password` | [events.saboreateruel.com](https://events.saboreateruel.com) |
| Organizador | `aprendizcella@gmail.com` | `password` | [miseventos.saboreateruel.com](https://miseventos.saboreateruel.com) |

> El checkout es simulado. Producción mantiene los pagos offline — no se procesan pagos reales en el despliegue público.

---

## ⚡ Stack

| Herramienta | Versión | Función |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel | 12.x | Framework |
| Livewire | 4.3 | UI reactiva full-stack |
| Livewire Volt | 1.10 | Single-file components |
| Tailwind CSS | 4.x | Utility-first CSS |
| Alpine.js | 3.x | Interactividad cliente ligera |
| Vite | 7.x | Bundler frontend |
| Pest | 4.x | Testing (Feature, Unit, Browser) |
| PHPStan + Larastan | Nivel 8 | Análisis estático |
| Rector | 2.x | Refactoring automático |
| Pint | 1.x | Code style (Laravel preset) |
| SonarQube | Community | Análisis de calidad y seguridad |
| Laravel Boost | 2.x | AI guidelines + MCP server |
| Laravel Horizon | 5.x | Gestión de colas Redis |
| Laravel Scout | 10.x | Búsqueda full-text (Meilisearch) |
| Laravel Sanctum | 4.x | Autenticación |
| Spatie Multitenancy | 4.x | Aislamiento multi-tenant |
| Spatie Permission | 8.x | Roles y permisos |
| Spatie Activitylog | 5.x | Auditoría de actividad |
| Spatie Health | 1.x | Monitorización |
| Stripe | 20.x | Pagos (simulado en prod) |
| DomPDF | 3.x | Generación de PDFs |
| Playwright | 1.x | Browser testing |

---

## 🧠 AI-Ready

El proyecto está instrumentado para desarrollo asistido por IA con skills, guidelines y configuración multi-agente:

```
.
├── .agents/skills/          → 19 skills de generación (actions, controllers, DTOs, tests, etc.)
├── .claude/skills/          → Claude Code skills (Laravel + Pest especializados)
├── .codex/config.toml       → Configuración OpenCode/Codex
├── .gemini/settings.json    → Configuración Gemini
├── .ai/                     → Guidelines siempre activas
│   ├── architecture.md      → Flujos de acción y presentación
│   ├── code-style.md        → Strict types, readonly, early return, final
│   ├── forbidden.md         → Restricciones para agentes AI
│   └── qa.md                → Pipeline QA obligatorio
├── .atl/skill-registry.md   → Índice de skills disponibles
└── AGENTS.md                → Configuración del agente orquestador
```

**Agentes configurados:** Claude Code, OpenCode (Codex), Gemini, GitHub Copilot, Cursor.

Integración con **Laravel Boost MCP** para acceso a documentación versionada, esquema de BD, logs y herramientas específicas del proyecto.

---

## 🚀 Setup local (Docker / Sail)

```bash
git clone git@github.com:aprendizcella/eventos.git
cd eventos
cp .env.example .env
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
npm install && npm run build
```

### Servicios incluidos en `compose.yaml`

`laravel.test` · `mariadb` · `redis` · `mailpit` · `minio` · `meilisearch` · `sonarqube` · `sonarqube-db`

### Arranque en desarrollo

```bash
composer dev
```

Levanta servidor, colas, logs (Pail) y Vite en paralelo.

---

## 🔒 QA Pipeline

```bash
./vendor/bin/sail composer qa
```

Ejecuta en orden:
1. **Rector** → dry-run (detecta mejoras de código)
2. **Pint** → verifica formato
3. **PHPStan** → análisis estático nivel 8
4. **Pest** → tests

Si Rector detecta cambios:
```bash
./vendor/bin/sail composer rector    # aplica cambios
./vendor/bin/sail composer qa        # verifica de nuevo
```

### Scripts disponibles

```bash
./vendor/bin/sail composer qa        # pipeline completo (rector --dry-run → pint --dirty → phpstan → test)
./vendor/bin/sail composer rector    # aplicar Rector
./vendor/bin/sail composer pint      # formatear código
./vendor/bin/sail composer phpstan   # análisis estático nivel 8
./vendor/bin/sail composer test      # ejecutar tests (Pest)
./vendor/bin/sail composer dev       # entorno de desarrollo (server + queue + logs + vite)
```

---

## 🔍 SonarQube

Análisis estático de calidad y seguridad con SonarQube Community.

```bash
./vendor/bin/sail up -d sonarqube sonarqube-db    # levantar SonarQube
./sonar.sh                            # ejecutar análisis
```

Panel disponible en `http://localhost:9000`

### Primer uso

1. Acceder a `http://localhost:9000` (usuario: `admin`, contraseña: `admin`)
2. Cambiar contraseña
3. Crear proyecto local con el key definido en `sonar-project.properties`
4. Generar token y añadirlo al `.env` como `SONAR_TOKEN`
5. Ejecutar `./sonar.sh`

### Cobertura de tests

```bash
mkdir -p build/logs
XDEBUG_MODE=coverage ./vendor/bin/sail php vendor/bin/pest --coverage-clover build/logs/clover.xml
```

---

## 🏗️ Arquitectura

- **Multitenant:** Aislamiento de datos por organizador vía `spatie/laravel-multitenancy`. Cada organizador accede desde su propio dominio.
- **Action Pattern:** Lógica de negocio en Actions invocables (`app/Actions/{Domain}/`). Los controllers son thin — solo coordinan.
- **DTOs + FormRequests:** Transporte de datos tipado entre capas. Las FormRequests construyen el DTO vía `toDto()`.
- **ViewModels + Resources:** Capa de presentación separada. ViewModels para endpoints multi-dato, Resources para formato JSON.
- **Livewire Volt SFC:** Componentes full-stack en un solo archivo para las secciones públicas y de organizador.
- **Alpine.js:** Interactividad cliente sin JS framework pesado.

---

## 📂 Estructura de documentación

| Documento | Contenido |
|---|---|
| `docs/README.md` | Índice completo de documentación |
| `docs/07-entrega-tfm/` | Plan de entrega, guion de vídeo, presentaciones |
| `openspec/` | Especificaciones formales (SDD) y cambios archivados |
| `.ai/` | Guidelines de arquitectura, estilo, prohibiciones y QA |

---

## 🌐 URLs públicas

| Recurso | URL |
|---|---|
| Despliegue | [events.saboreateruel.com](https://events.saboreateruel.com) |
| Demo organizador | [miseventos.saboreateruel.com](https://miseventos.saboreateruel.com) |
| Slides TFM | [/tfm/slides](https://events.saboreateruel.com/tfm/slides) |
| Vídeo TFM | [/tfm/videos](https://events.saboreateruel.com/tfm/videos) |
| Repositorio | [github.com/aprendizcella/eventos](https://github.com/aprendizcella/eventos) |
