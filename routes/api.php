<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\BusquedaController;
use App\Http\Controllers\Api\CaptacionController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ColocacionClienteController;
use App\Http\Controllers\Api\ColocacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\HistorialController;
use App\Http\Controllers\Api\InmuebleCaptadoController;
use App\Http\Controllers\Api\InmuebleCaptadoClienteController;
use App\Http\Controllers\Api\InmuebleController;
use App\Http\Controllers\Api\InteresadoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PasarInformacionController;
use App\Http\Controllers\Api\PasarInformacionClienteController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SuscripcionController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VisitaController;
use App\Http\Controllers\Api\VisitaClienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('profile/photo-url', [AuthController::class, 'updatePhotoUrl']);
});

Route::prefix('catalogos')->group(function () {
    Route::get('tipos-inmueble', [CatalogoController::class, 'tiposInmueble']);
    Route::get('zonas', [CatalogoController::class, 'zonas']);
    Route::get('operaciones', [CatalogoController::class, 'operaciones']);
    Route::get('estados-amc', [CatalogoController::class, 'estadosAmc']);
    Route::get('acciones', [CatalogoController::class, 'acciones']);
    Route::get('monedas', [CatalogoController::class, 'monedas']);
    Route::get('asesores', [CatalogoController::class, 'asesores']);
});

Route::get('clientes/search', [ClienteController::class, 'search']);
Route::get('clientes/{id}/relaciones', [ClienteController::class, 'relations']);
Route::post('clientes/{id}/inmuebles', [ClienteController::class, 'storeInmueble']);
Route::apiResource('clientes', ClienteController::class);

Route::apiResource('inmuebles', InmuebleController::class);
Route::post('inmuebles/{id}/fotos', [InmuebleController::class, 'storeFoto']);
Route::delete('inmuebles/fotos/{id}', [InmuebleController::class, 'destroyFoto']);
Route::post('inmuebles/{id}/documentos', [InmuebleController::class, 'storeDocumento']);
Route::delete('inmuebles/documentos/{id}', [InmuebleController::class, 'destroyDocumento']);

Route::get('captaciones/historial', [CaptacionController::class, 'historialGlobal']);
Route::get('captaciones/proximas-acciones', [CaptacionController::class, 'proximasAcciones']);
Route::get('captaciones/{id}/historial', [CaptacionController::class, 'historial']);
Route::get('captaciones/historial/papelera', [CaptacionController::class, 'historialPapelera']);
Route::post('captaciones/historial/soft-delete', [CaptacionController::class, 'softDeleteHistorialGrupo']);
Route::post('captaciones/historial/restore', [CaptacionController::class, 'restoreHistorialGrupo']);
Route::apiResource('captaciones', CaptacionController::class)->except(['destroy']);

Route::get('inmuebles-captados/{id}/historial', [InmuebleCaptadoController::class, 'historial']);
Route::get('inmuebles-captados/historial', [InmuebleCaptadoController::class, 'historialGlobal']);
Route::get('inmuebles-captados/historial/papelera', [InmuebleCaptadoController::class, 'historialPapelera']);
Route::post('inmuebles-captados/historial/soft-delete', [InmuebleCaptadoController::class, 'softDeleteHistorialGrupo']);
Route::post('inmuebles-captados/historial/restore', [InmuebleCaptadoController::class, 'restoreHistorialGrupo']);
Route::get('inmuebles-captados/clientes/search', [InmuebleCaptadoClienteController::class, 'search']);
Route::get('inmuebles-captados/clientes/{id}/relaciones', [InmuebleCaptadoClienteController::class, 'relations']);
Route::post('inmuebles-captados/clientes/{id}/inmuebles', [InmuebleCaptadoClienteController::class, 'storeInmueble']);
Route::apiResource('inmuebles-captados/clientes', InmuebleCaptadoClienteController::class)->names('inmuebles_captados.clientes');
Route::apiResource('inmuebles-captados', InmuebleCaptadoController::class)->except(['destroy']);

Route::apiResource('busquedas', BusquedaController::class)->except(['destroy']);
Route::post('busquedas/{id}/inmuebles', [BusquedaController::class, 'attachInmueble']);
Route::delete('busquedas/inmuebles/{id}', [BusquedaController::class, 'detachInmueble']);

Route::get('colocaciones/historial', [ColocacionController::class, 'historialGlobal']);
Route::get('colocaciones/{id}/historial', [ColocacionController::class, 'historial']);
Route::get('colocaciones/historial/papelera', [ColocacionController::class, 'historialPapelera']);
Route::post('colocaciones/historial/soft-delete', [ColocacionController::class, 'softDeleteHistorialGrupo']);
Route::post('colocaciones/historial/restore', [ColocacionController::class, 'restoreHistorialGrupo']);
Route::get('colocaciones/clientes/search', [ColocacionClienteController::class, 'search']);
Route::get('colocaciones/clientes/{id}/relaciones', [ColocacionClienteController::class, 'relations']);
Route::post('colocaciones/clientes/{id}/inmuebles', [ColocacionClienteController::class, 'storeInmueble']);
Route::apiResource('colocaciones/clientes', ColocacionClienteController::class)->names('colocaciones.clientes');
Route::apiResource('colocaciones', ColocacionController::class)->except(['destroy']);

Route::get('interesados/search', [InteresadoController::class, 'search']);
Route::apiResource('interesados', InteresadoController::class)->except(['destroy']);

Route::get('visitas/historial', [VisitaController::class, 'historialGlobal']);
Route::get('visitas/historial/papelera', [VisitaController::class, 'historialPapelera']);
Route::post('visitas/historial/soft-delete', [VisitaController::class, 'softDeleteHistorialGrupo']);
Route::post('visitas/historial/restore', [VisitaController::class, 'restoreHistorialGrupo']);
Route::get('visitas/{id}/acciones', [VisitaController::class, 'acciones']);
Route::post('visitas/{id}/acciones', [VisitaController::class, 'storeAccion']);
Route::put('visitas/acciones/{id}', [VisitaController::class, 'updateAccion']);
Route::get('visitas/clientes/search', [VisitaClienteController::class, 'search']);
Route::get('visitas/clientes/{id}/relaciones', [VisitaClienteController::class, 'relations']);
Route::post('visitas/clientes/{id}/inmuebles', [VisitaClienteController::class, 'storeInmueble']);
Route::apiResource('visitas/clientes', VisitaClienteController::class)->names('visitas.clientes');
Route::apiResource('visitas', VisitaController::class)->except(['destroy']);

Route::get('pasar-informacion/historial', [PasarInformacionController::class, 'historialGlobal']);
Route::get('pasar-informacion/{id}/historial', [PasarInformacionController::class, 'historial']);
Route::get('pasar-informacion/historial/papelera', [PasarInformacionController::class, 'historialPapelera']);
Route::post('pasar-informacion/historial/soft-delete', [PasarInformacionController::class, 'softDeleteHistorialGrupo']);
Route::post('pasar-informacion/historial/restore', [PasarInformacionController::class, 'restoreHistorialGrupo']);
Route::get('pasar-informacion/clientes/search', [PasarInformacionClienteController::class, 'search']);
Route::get('pasar-informacion/clientes/{id}/relaciones', [PasarInformacionClienteController::class, 'relations']);
Route::post('pasar-informacion/clientes/{id}/inmuebles', [PasarInformacionClienteController::class, 'storeInmueble']);
Route::apiResource('pasar-informacion/clientes', PasarInformacionClienteController::class)->names('pasar_informacion.clientes');
Route::apiResource('pasar-informacion', PasarInformacionController::class)->except(['destroy']);

Route::get('historial', [HistorialController::class, 'index']);
Route::post('historial/soft-delete', [HistorialController::class, 'softDelete']);
Route::post('historial/soft-delete-all', [HistorialController::class, 'softDeleteAll']);
Route::post('historial/restore-all', [HistorialController::class, 'restoreAll']);

Route::apiResource('tareas', TareaController::class)->only(['index', 'store', 'update']);

Route::get('dashboard', [DashboardController::class, 'index']);
Route::post('device-tokens', [DeviceTokenController::class, 'store']);
Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
Route::get('notificaciones', [NotificacionController::class, 'index']);
Route::post('notificaciones/{id}/leer', [NotificacionController::class, 'markRead']);

Route::middleware('auth.admin')->group(function () {
    Route::get('admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::apiResource('planes', PlanController::class);
    Route::apiResource('usuarios', UsuarioController::class);
});

Route::apiResource('suscripciones', SuscripcionController::class);
