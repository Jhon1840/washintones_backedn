# API Reference

Esta es la guía rápida para consumir la API REST del proyecto. Toda la API vive bajo la ruta base `/api`, por lo que al ejecutar `php artisan serve --host=0.0.0.0 --port=8081` el endpoint raíz queda en `http://localhost:8081/api`.

## Convenciones generales

- **Formato**: todas las peticiones y respuestas usan JSON (UTF-8).
- **Headers**: incluye `Accept: application/json` y `Content-Type: application/json` para peticiones con cuerpo.
- **Autenticacion**: usa tokens tipo Bearer generados por el login (ver siguiente seccion).
- **Paginacion**: cuando se implemente, seguira el formato estandar de Laravel (`data`, `links`, `meta`).

## Autenticacion

Los usuarios de negocio viven en la tabla `usuarios`. El seed `UsuarioSeeder` crea cuentas demo listas para probar el panel de admin y el flujo normal:

| Email | Password | Rol |
| --- | --- | --- |
| `admin@gmail.com` | `123456` | Admin (`es_admin: true`) |
| `carla@gmail.com` | `123456` | Usuario activo con flujo completo |
| `luis.ortega@freddy-demo.test` | `password` | Usuario activo |

### Login

```
POST /api/auth/login
Content-Type: application/json

{
  "email": "carla@gmail.com",
  "password": "123456"
}
```

Respuesta exitosa:

```json
{
  "access_token": "eyxhb...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "nombre": "Carla Ramirez",
    "email": "carla@gmail.com",
    "telefono": "555-0101",
    "es_admin": false
  }
}
```

Incluye el token en cada request subsiguiente:

```
Authorization: Bearer {access_token}
```

### Refresh

```
POST /api/auth/refresh
Authorization: Bearer {token}
```

Devuelve un token nuevo con el mismo formato y renueva el tiempo de expiracion (60 minutos).

### Logout

```
POST /api/auth/logout
Authorization: Bearer {token}
```

Responde con un mensaje informativo; el cliente es responsable de descartar el token.

### Perfil actual

```
GET /api/auth/me
Authorization: Bearer {token}
```

Devuelve los campos basicos (`id`, `nombre`, `email`, `telefono`, `activo`, `es_admin`).

## Catologos

Estos endpoints devuelven la informacion necesaria para poblar formularios y validar relaciones.

- `GET /api/catalogos/tipos-inmueble`
- `GET /api/catalogos/zonas`
- `GET /api/catalogos/operaciones`
- `GET /api/catalogos/estados-amc`
- `GET /api/catalogos/acciones`
- `GET /api/catalogos/monedas`
- `GET /api/catalogos/asesores`

Notas sobre tipos de inmueble:

- `tipos-inmueble` mezcla valores globales + valores personalizados por usuario (requiere token).
- Si el cliente envia un `tipo`/`tipo_inmueble` que no existe, el backend lo crea y lo asocia al usuario logeado.
- Los tipos personalizados solo aparecen para ese usuario.

## Recursos principales

Cada recurso usa `Route::apiResource`, por lo que expone `index`, `store`, `show`, `update` y `destroy` (cuando no se excluye). A continuacion se listan los mas relevantes:

### Usuarios

- `GET /api/usuarios`
- `POST /api/usuarios`
- `GET /api/usuarios/{id}`
- `PUT/PATCH /api/usuarios/{id}`
- `DELETE /api/usuarios/{id}`

 Campos esperados: `nombre`, `email`, `telefono`, `password`, `activo`, `es_admin`.
 
 - Endpoints disponibles solo para administradores autenticados (`middleware: auth.admin`).
 - Usa `es_admin: true` para los usuarios con rol administrativo y `false` para el resto.
 - El seed `FlujoCompletoSeeder` enlaza a `carla@gmail.com` con clientes, captaciones, visitas, colocar e inmuebles captados para que exista un flujo end-to-end listo para probar.

### Planes (solo admin)

- `GET /api/planes`
- `POST /api/planes`
- `GET /api/planes/{id}`
- `PUT/PATCH /api/planes/{id}`
- `DELETE /api/planes/{id}`

Campos: `nombre`, `duracion_dias` (dias de vigencia, puede ser `null` para planes ilimitados), `precio`, `activo`.

- Endpoints disponibles solo para administradores autenticados (`middleware: auth.admin`).
- Si no env¡as el campo `activo` al crear (`POST`), el plan se guarda por defecto como activo (`activo: true`).

### Clientes

- `GET /api/clientes`
- `POST /api/clientes`
- `GET /api/clientes/{id}`
- `PUT/PATCH /api/clientes/{id}`
- `DELETE /api/clientes/{id}`
- `GET /api/clientes/search?term=texto`
- `GET /api/clientes/{id}/relaciones`

Campos: `nombre`, `telefono`, `email`.

El endpoint `relaciones` sirve para poblar los autocompletados móviles: devuelve los inmuebles ligados al cliente y los interesados/contactos más recientes que aparecen en `historial_acciones`. Ejemplo:

```json
{
  "message": "Relaciones del cliente recuperadas.",
  "cliente": {
    "id": 4,
    "nombre": "Residencial Aurora",
    "telefono": "555-0199",
    "email": "aurora@example.com"
  },
  "data": {
    "inmuebles": [
      { "id": 12, "nombre": "Calle Aurora 742, Centro", "descripcion": "Departamento modelo" }
    ],
    "contactos": [
      { "id": 7, "nombre": "Luis Perez", "telefono": "777-1111", "ultima_accion": "2026-01-02" }
    ]
  }
}
```

### Inmuebles

- `GET /api/inmuebles`
- `POST /api/inmuebles`
- `GET /api/inmuebles/{id}`
- `PUT/PATCH /api/inmuebles/{id}`
- `DELETE /api/inmuebles/{id}`
- `POST /api/inmuebles/{id}/fotos`
- `DELETE /api/inmuebles/fotos/{id}`
- `POST /api/inmuebles/{id}/documentos`
- `DELETE /api/inmuebles/documentos/{id}`

Payload tipico:

```json
{
  "cliente_id": 1,
  "direccion": "Av Reforma 123, Centro",
  "descripcion": "Departamento remodelado...",
  "tipo_id": 2,
  "zona_id": 1,
  "operacion_id": 1,
  "amc_estado_id": 3,
  "valor_estimado": 9500000,
  "moneda_id": 2
}
```

### Captaciones

- `GET /api/captaciones`
- `POST /api/captaciones`
- `GET /api/captaciones/{id}`
- `PUT/PATCH /api/captaciones/{id}`
- `GET /api/captaciones/proximas-acciones`
- `GET /api/captaciones/{id}/historial`
- `GET /api/captaciones/historial`
- `GET /api/captaciones/historial/papelera`
- `POST /api/captaciones/historial/soft-delete` (body: `captacion_id`)
- `POST /api/captaciones/historial/restore` (body: `captacion_id`)

`destroy` esta deshabilitado (`except(['destroy'])`).

### Busquedas

- `GET /api/busquedas`
- `POST /api/busquedas`
- `GET /api/busquedas/{id}`
- `PUT/PATCH /api/busquedas/{id}`
- `POST /api/busquedas/{id}/inmuebles`
- `DELETE /api/busquedas/inmuebles/{id}`

### Colocaciones

`apiResource` sin `destroy`, mas:

- `GET /api/colocaciones/{id}/historial`
- `GET /api/colocaciones/historial`
- `GET /api/colocaciones/historial/papelera`
- `POST /api/colocaciones/historial/soft-delete` (body: `colocacion_id`)
- `POST /api/colocaciones/historial/restore` (body: `colocacion_id`)

### Interesados

`apiResource` sin `destroy` y `GET /api/interesados/search`.

### Visitas

- `apiResource('visitas')->except(['destroy'])`
- `GET /api/visitas/{id}/acciones`
- `POST /api/visitas/{id}/acciones`
- `PUT /api/visitas/acciones/{id}`
- `GET /api/visitas/historial`
- `GET /api/visitas/historial/papelera`
- `POST /api/visitas/historial/soft-delete` (body: `visita_id`)
- `POST /api/visitas/historial/restore` (body: `visita_id`)

### Pasar informacion

`apiResource('pasar-informacion')->except(['destroy'])` mas:

- `GET /api/pasar-informacion/{id}/historial`
- `GET /api/pasar-informacion/historial`
- `GET /api/pasar-informacion/historial/papelera`
- `POST /api/pasar-informacion/historial/soft-delete` (body: `pasar_informacion_id`)
- `POST /api/pasar-informacion/historial/restore` (body: `pasar_informacion_id`)

### Suscripciones

- `GET /api/suscripciones`
- `POST /api/suscripciones`
- `GET /api/suscripciones/{id}`
- `PUT/PATCH /api/suscripciones/{id}`
- `DELETE /api/suscripciones/{id}`

Campos: `usuario_id`, `plan_id`, `estado`, `precio_mensual`, `fecha_inicio`, `fecha_fin`, `ultimo_pago`.

- Al crear una suscripcion, si no env¡as el campo `estado`, se guarda por defecto como activa (`estado: "activa"`).
- Si el plan asociado tiene `duracion_dias` distinto de `null`, el backend calcula siempre `precio_mensual` a partir del campo `precio` del plan y fija `fecha_fin = fecha_inicio + duracion_dias`.
- Si el plan es ilimitado (`duracion_dias = null`), `fecha_fin` queda en `null` y debes indicar `precio_mensual` al crear (o actualizar) la suscripcion; de lo contrario se devuelve un error de validacion (`422`).

### Otros endpoints

- `GET /api/inmuebles-captados`
- `GET /api/inmuebles-captados/{id}`
- `POST /api/inmuebles-captados`
- `PUT/PATCH /api/inmuebles-captados/{id}`
- `GET /api/inmuebles-captados/{id}/historial`
- `GET /api/inmuebles-captados/historial`
- `GET /api/inmuebles-captados/historial/papelera`
- `POST /api/inmuebles-captados/historial/soft-delete` (body: `inmueble_captado_id`)
- `POST /api/inmuebles-captados/historial/restore` (body: `inmueble_captado_id`)
- `GET /api/historial`
- `apiResource('tareas')->only(['index','store','update'])`
- `GET /api/dashboard`
- `GET /api/admin/dashboard` (solo admin)

### Dashboard admin

Requiere autenticación con usuario admin (`middleware: auth.admin`).

```
GET /api/admin/dashboard
Authorization: Bearer {token}
```

Notas:
- Las ganancias se calculan con base en `suscripciones` activas (`estado: activa` y `fecha_fin` nula o en el futuro).
- `ganancias_mes` es la suma de `precio_mensual` de las suscripciones activas.
- `ganancias_anio` es `ganancias_mes * 12`.
- La serie semanal suma `precio_mensual` por `fecha_inicio` en los últimos 7 días.

Respuesta ejemplo:

```json
{
  "total_usuarios": 12,
  "ganancias_mes": 3500000,
  "ganancias_anio": 14250000,
  "serie_semanal": [
    { "fecha": "2026-01-06", "label": "L", "total": 0 },
    { "fecha": "2026-01-07", "label": "M", "total": 1200000 },
    { "fecha": "2026-01-08", "label": "M", "total": 0 },
    { "fecha": "2026-01-09", "label": "J", "total": 850000 },
    { "fecha": "2026-01-10", "label": "V", "total": 0 },
    { "fecha": "2026-01-11", "label": "S", "total": 600000 },
    { "fecha": "2026-01-12", "label": "D", "total": 0 }
  ],
  "message": "Dashboard admin actualizado."
}
```

## Ejemplos de integracion rapida

### Crear cliente e inmueble

```bash
TOKEN="..."
curl -X POST http://localhost:8081/api/clientes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Cliente Demo","telefono":"555-9999","email":"cliente@example.com"}'
```

```bash
curl -X POST http://localhost:8081/api/inmuebles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 1,
    "direccion": "Calle 1 #123",
    "descripcion": "Propiedad demo",
    "tipo_id": 1,
    "zona_id": 2,
    "operacion_id": 1,
    "amc_estado_id": 1,
    "valor_estimado": 2500000,
    "moneda_id": 2
  }'
```

### Consultar catalogo de tipos de inmueble

```bash
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8081/api/catalogos/tipos-inmueble
```

### Crear plan y suscripcion

Plan de pagos mensual activo por defecto:

```bash
curl -X POST http://localhost:8081/api/planes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Plan Mensual Básico",
    "duracion_dias": 30,
    "precio": 1200000
  }'
```

Como no se env¡a `activo`, el plan se crea con `activo: true`.

Suscripcion activa por defecto usando ese plan:

```bash
curl -X POST http://localhost:8081/api/suscripciones \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_id": 2,
    "plan_id": 1,
    "fecha_inicio": "2026-01-10"
  }'
```

Al no enviar `estado`, la suscripcion se guarda con `estado: "activa"`. Para planes con `duracion_dias` definido, el backend toma `precio_mensual` desde el `precio` del plan y calcula `fecha_fin` sumando los dias de duracion a `fecha_inicio`.
