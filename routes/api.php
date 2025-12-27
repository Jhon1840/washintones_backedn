<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusquedaController;
use App\Http\Controllers\Api\CaptacionController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ColocacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HistorialController;
use App\Http\Controllers\Api\InmuebleCaptadoController;
use App\Http\Controllers\Api\InmuebleController;
use App\Http\Controllers\Api\InteresadoController;
use App\Http\Controllers\Api\PasarInformacionController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VisitaController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::apiResource('usuarios', UsuarioController::class);

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
Route::apiResource('clientes', ClienteController::class);

Route::apiResource('inmuebles', InmuebleController::class);
Route::post('inmuebles/{id}/fotos', [InmuebleController::class, 'storeFoto']);
Route::delete('inmuebles/fotos/{id}', [InmuebleController::class, 'destroyFoto']);
Route::post('inmuebles/{id}/documentos', [InmuebleController::class, 'storeDocumento']);
Route::delete('inmuebles/documentos/{id}', [InmuebleController::class, 'destroyDocumento']);

Route::get('captaciones/proximas-acciones', [CaptacionController::class, 'proximasAcciones']);
Route::get('captaciones/{id}/historial', [CaptacionController::class, 'historial']);
Route::apiResource('captaciones', CaptacionController::class)->except(['destroy']);

Route::get('inmuebles-captados/{id}/historial', [InmuebleCaptadoController::class, 'historial']);
Route::apiResource('inmuebles-captados', InmuebleCaptadoController::class)->except(['destroy']);

Route::apiResource('busquedas', BusquedaController::class)->except(['destroy']);
Route::post('busquedas/{id}/inmuebles', [BusquedaController::class, 'attachInmueble']);
Route::delete('busquedas/inmuebles/{id}', [BusquedaController::class, 'detachInmueble']);

Route::get('colocaciones/{id}/historial', [ColocacionController::class, 'historial']);
Route::apiResource('colocaciones', ColocacionController::class)->except(['destroy']);

Route::get('interesados/search', [InteresadoController::class, 'search']);
Route::apiResource('interesados', InteresadoController::class)->except(['destroy']);

Route::get('visitas/{id}/acciones', [VisitaController::class, 'acciones']);
Route::post('visitas/{id}/acciones', [VisitaController::class, 'storeAccion']);
Route::put('visitas/acciones/{id}', [VisitaController::class, 'updateAccion']);
Route::apiResource('visitas', VisitaController::class)->except(['destroy']);

Route::get('pasar-informacion/{id}/historial', [PasarInformacionController::class, 'historial']);
Route::apiResource('pasar-informacion', PasarInformacionController::class)->except(['destroy']);

Route::get('historial', [HistorialController::class, 'index']);

Route::apiResource('tareas', TareaController::class)->only(['index', 'store', 'update']);

Route::get('dashboard', [DashboardController::class, 'index']);
