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
