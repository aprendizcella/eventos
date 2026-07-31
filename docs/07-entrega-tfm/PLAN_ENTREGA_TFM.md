# Plan de entrega del TFM

Este documento organiza la preparación de la entrega y la demostración del TFM de la plataforma de gestión y descubrimiento de eventos. Distingue expresamente entre el estado verificado, los objetivos de entrega y el roadmap para evitar atribuir al producto funcionalidades no desplegadas.

## Hitos

| Hito | Fecha | Estado |
|---|---:|---|
| Objetivo de presentación | 2 de agosto de 2026 | Objetivo |
| Entrega formal del TFM | 24 de agosto de 2026 | Fecha acordada |

## Producto

La aplicación es una plataforma de gestión y descubrimiento de eventos dirigida a dos perfiles principales:

- Organizadores que se dan de alta, gestionan su actividad y crean eventos.
- Asistentes que descubren eventos y realizan una compra mediante un flujo simulado.

## Estado verificado

| Elemento | Estado verificado | Uso previsto en la entrega |
|---|---|---|
| Repositorio público | `https://github.com/aprendizcella/eventos` | Enlazar en la entrega y materiales públicos. |
| Despliegue público | `https://events.saboreateruel.com/` | Demostrar los recorridos disponibles de organizador y descubrimiento. |
| Dominio de organizador | `https://miseventos.saboreateruel.com/` responde públicamente y selecciona el contexto del organizador. | Demostrar el valor multi-tenant y multidominio sin confundir los datos entre organizadores. |
| Checkout simulado | Disponible solo en local/testing. | Mostrarlo en vídeo desde un entorno local claramente rotulado. |
| Pagos en producción | El despliegue indica que los pagos están offline. | No presentar ni atribuir pagos reales al despliegue. |
| README raíz | Pendiente de actualización en una fase posterior. | No modificarlo dentro de esta fase documental. |

## Recorridos demostrables

### 1. Organizador: alta, gestión y creación de eventos

1. Acceder como organizador con credenciales de demostración seguras pendientes de confirmar.
2. Mostrar el alta o la gestión del perfil de organizador disponible en el entorno.
3. Crear uno o varios eventos y revisar su gestión posterior.
4. Mostrar el acceso desde el dominio del organizador para evidenciar el aislamiento por tenant.

### 2. Asistente: búsqueda y descubrimiento

1. Acceder al catálogo público en el despliegue.
2. Buscar y explorar eventos.
3. Abrir el detalle de un evento para enlazar el recorrido con la compra simulada.

### 3. Asistente: compra con pago simulado

1. Indicar al inicio del segmento que se usa un entorno local/testing.
2. Mostrar el proceso de checkout simulado.
3. Reiterar que no se procesa ningún pago real.
4. No usar el despliegue público para afirmar que el checkout o los pagos están operativos.

## Plan de documentación

| Entregable | Contenido mínimo | Estado |
|---|---|---|
| Plan de entrega | Hitos, alcance de la demostración, calendario, riesgos y decisiones pendientes. | Creado en este documento. |
| README del repositorio | Descripción del producto, instalación, stack completo, credenciales demo, AI-ready setup, URLs y límites del checkout. | ✅ Completado (2026-07-31). |
| Guion del vídeo | Secuencia de 8-10 minutos, mensajes de contexto y transiciones entre entornos. | ✅ Documento creado (`GUION_VIDEO_TFM_DEFINITIVO_PROPUESTO.md`). Grabación pendiente (usuario). |
| Presentación | Problema, propuesta, arquitectura relevante, demostración, límites y roadmap. | ✅ Accesible en `/tfm/slides`. PPTX + PDF preview. |
| Checklist final | URLs públicas, credenciales seguras, enlaces de slides y vídeo. | ✅ URLs confirmadas. Credenciales documentadas en README. |

## Formato de presentación

El borrador actual se entrega como PowerPoint editable y usa únicamente capturas verificadas del producto. Reveal.js queda como alternativa futura para una publicación web; no está instalado ni forma parte del alcance actual.

## Guion del vídeo

Duración objetivo: 8-10 minutos. La grabación corresponde al usuario.

| Bloque | Duración orientativa | Contenido |
|---|---:|---|
| Contexto y propuesta | 1 minuto | Problema que aborda la plataforma y perfiles de usuario. |
| Flujo del organizador | 2-3 minutos | Alta o gestión y creación de eventos. |
| Descubrimiento | 2 minutos | Búsqueda, exploración y detalle de eventos en el despliegue. |
| Checkout simulado | 2 minutos | Flujo local/testing claramente identificado y sin pagos reales. |
| Cierre | 1-2 minutos | Resultados, límites actuales y roadmap. |

## Checklist de URLs y credenciales

Antes de publicar los materiales finales, confirmar lo siguiente:

- [x] El repositorio público enlaza a `https://github.com/aprendizcella/eventos`.
- [x] El despliegue enlaza a `https://events.saboreateruel.com/`.
- [x] El dominio de demostración del organizador enlaza a `https://miseventos.saboreateruel.com/`.
- [x] Las credenciales de demostración son seguras, de alcance limitado y están verificadas (documentadas en README).
- [ ] El vídeo identifica el entorno local/testing durante el checkout simulado (grabación pendiente).
- [x] La URL pública de las diapositivas está confirmada (`/tfm/slides`).
- [x] La URL pública del vídeo está confirmada (`/tfm/videos`).
- [x] No se afirma que el despliegue procese pagos reales (aclarado en README).

## Calendario de trabajo

| Periodo | Resultado esperado |
|---|---|
| Antes del 2 de agosto de 2026 | Recorridos ensayados, guion cerrado, presentación preparada y checklist de acceso completado. |
| 2 de agosto de 2026 | Materiales listos para la presentación objetivo. |
| Del 3 al 23 de agosto de 2026 | Ajustes, publicación de enlaces y consolidación de la documentación pendiente autorizada. |
| 24 de agosto de 2026 | Entrega formal con enlaces y evidencias validadas. |

## Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Confundir el checkout local con el despliegue público | Se podría atribuir erróneamente una pasarela de pago real. | Rotular el entorno local/testing en vídeo y explicar que producción mantiene los pagos offline. |
| Credenciales no seguras o no disponibles | Bloqueo de la demostración de roles. | Verificar cuentas de demostración de mínimo privilegio antes de grabar. |
| Enlaces públicos no disponibles | La revisión no puede reproducir los materiales. | Validar repositorio, despliegue, slides y vídeo desde una sesión no autenticada. |
| Alcance excesivo antes de la fecha objetivo | Retraso de la preparación de la presentación. | Priorizar los tres recorridos demostrables y diferir mejoras no necesarias. |
| Afirmar funcionalidades futuras como actuales | Inconsistencia entre discurso y producto. | Mantener el roadmap separado y no demostrarlo como funcionalidad desplegada. |

## Roadmap

La selección del tenant por dominio personalizado de organizador está desplegada y forma parte de los recorridos demostrables. Como evolución futura, se podrá automatizar el aprovisionamiento y la verificación de dominios para reducir la intervención operativa.

## Decisiones pendientes

- Grabación del vídeo (8-10 min) por parte del usuario.
- Ensayo de los tres recorridos demostrables (organizador, asistente, checkout simulado).
