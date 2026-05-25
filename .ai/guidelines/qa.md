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
