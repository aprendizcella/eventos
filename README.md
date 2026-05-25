# eventos

> Base Laravel 12 orientada a desarrollo serio, con PHP 8.4, Sail, QA automatizado y herramientas de calidad.

---

## Stack base

| Herramienta | Estado |
|---|---|
| PHP | 8.4 |
| Laravel | 12.x |
| Composer | scripts de setup, dev, qa, test |
| Docker / Sail | entorno local reproducible |
| Pest | tests |
| PHPStan + Larastan | análisis estático |
| Pint | code style |
| Rector + rector-laravel | refactorización automática |
| SonarQube | quality gate adicional |

---

## Entorno local

El proyecto usa Sail con PHP 8.4 y los servicios de soporte definidos en `compose.yaml`:

- `laravel.test`
- `mariadb`
- `redis`
- `mailpit`
- `minio`
- `meilisearch`
- `sonarqube`
- `sonarqube-db`

### Arranque habitual

```bash
composer setup
```

### Desarrollo

```bash
composer dev
```

### Calidad

```bash
composer qa
```

### Tests

```bash
composer test
```

### Formato

```bash
composer pint
```

### Análisis estático

```bash
composer phpstan
```

### Rector

```bash
composer rector
```

---

## SonarQube

El proyecto incluye SonarQube como capa adicional de calidad.

### Archivos relacionados

- `compose.yaml`
- `sonar-project.properties`
- `sonar.sh`

### Uso

1. Levantar el stack local.
2. Definir `SONAR_TOKEN` en `.env`.
3. Ejecutar:

```bash
./sonar.sh
```

---

## Convenciones actuales

- PHP objetivo: **8.4**
- Rector configurado en la raíz del proyecto
- SonarQube se añade como complemento, no como sustituto del stack actual
- Boost y `pest-plugin-browser` se dejan para una iteración posterior

---

## Estado del proyecto

Este repositorio se está alineando con una base de trabajo más disciplinada:

- contrato de calidad explícito
- herramientas estándar por Composer
- infraestructura local reproducible
- documentación más cercana al estado real del código

