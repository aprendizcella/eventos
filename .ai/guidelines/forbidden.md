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
