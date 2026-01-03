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

Estos endpoints devuelven la informacion necesaria para poblar formularios y validar relaciones. Actualmente regresan un mensaje placeholder hasta que el `CatalogoController` consulte la base.

- `GET /api/catalogos/tipos-inmueble`
- `GET /api/catalogos/zonas`
- `GET /api/catalogos/operaciones`
- `GET /api/catalogos/estados-amc`
- `GET /api/catalogos/acciones`
- `GET /api/catalogos/monedas`
- `GET /api/catalogos/asesores`

Los seeds (`CatalogoSeeder`) ya insertan datos para cada catalogo, por lo que solo falta exponerlos desde el controlador.

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

### Interesados

`apiResource` sin `destroy` y `GET /api/interesados/search`.

### Visitas

- `apiResource('visitas')->except(['destroy'])`
- `GET /api/visitas/{id}/acciones`
- `POST /api/visitas/{id}/acciones`
- `PUT /api/visitas/acciones/{id}`
- `GET /api/visitas/historial`

### Pasar informacion

`apiResource('pasar-informacion')->except(['destroy'])` mas:

- `GET /api/pasar-informacion/{id}/historial`
- `GET /api/pasar-informacion/historial`

### Otros endpoints

- `GET /api/inmuebles-captados`
- `GET /api/inmuebles-captados/{id}`
- `POST /api/inmuebles-captados`
- `PUT/PATCH /api/inmuebles-captados/{id}`
- `GET /api/inmuebles-captados/{id}/historial`
- `GET /api/historial`
- `apiResource('tareas')->only(['index','store','update'])`
- `GET /api/dashboard`

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

## Nota sobre implementaciones pendientes

Varios controladores actualmente devuelven mensajes de "pendiente de implementacion". La documentacion anterior describe el contrato esperado para cuando se complete cada recurso. Para pruebas rapidas usa los datos de seed y los endpoints ya implementados (`auth`, `usuarios`, etc.), luego extiende los controladores restantes siguiendo el mismo patron.
