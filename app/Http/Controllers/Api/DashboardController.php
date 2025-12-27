<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'acciones_hoy' => 0,
            'visitas_hoy' => 0,
            'tareas_vencidas' => 0,
            'captaciones_activas' => 0,
            'colocaciones_activas' => 0,
            'message' => 'Dashboard pendiente de implementación.',
        ]);
    }
}
