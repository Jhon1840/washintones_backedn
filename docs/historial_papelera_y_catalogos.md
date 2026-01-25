# Historial y catalogos (papelera + opciones personalizadas)

Este documento resume el flujo de papelera/restauracion de historiales y el
comportamiento de opciones personalizadas en catalogos (tipos de inmueble).

## Catalogos: tipos de inmueble personalizados por usuario

- Endpoint: `GET /api/catalogos/tipos-inmueble` (requiere token).
- Respuesta: mezcla tipos globales (usuario_id = NULL) + tipos creados por el
  usuario autenticado.
- Si el cliente envia un `tipo`/`tipo_inmueble` no existente, el backend crea
  el registro y lo asocia al usuario logeado.
- Los tipos personalizados no se comparten con otros usuarios.

## Historial: papelera y restauracion por modulo

Todos los modulos cuentan con papelera (listar), soft-delete por grupo y
restauracion por grupo. Los historiales se filtran por `usuario_id` y usan la
columna `deleted_at` en sus tablas de historial.

### Captaciones

- `GET /api/captaciones/historial/papelera`
- `POST /api/captaciones/historial/soft-delete` (body: `captacion_id`)
- `POST /api/captaciones/historial/restore` (body: `captacion_id`)

### Colocaciones

- `GET /api/colocaciones/historial/papelera`
- `POST /api/colocaciones/historial/soft-delete` (body: `colocacion_id`)
- `POST /api/colocaciones/historial/restore` (body: `colocacion_id`)

### Visitas

- `GET /api/visitas/historial/papelera`
- `POST /api/visitas/historial/soft-delete` (body: `visita_id`)
- `POST /api/visitas/historial/restore` (body: `visita_id`)

### Pasar informacion

- `GET /api/pasar-informacion/historial/papelera`
- `POST /api/pasar-informacion/historial/soft-delete` (body: `pasar_informacion_id`)
- `POST /api/pasar-informacion/historial/restore` (body: `pasar_informacion_id`)

### Inmuebles captados

- `GET /api/inmuebles-captados/historial/papelera`
- `POST /api/inmuebles-captados/historial/soft-delete` (body: `inmueble_captado_id`)
- `POST /api/inmuebles-captados/historial/restore` (body: `inmueble_captado_id`)
