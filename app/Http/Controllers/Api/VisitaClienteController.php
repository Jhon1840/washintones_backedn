<?php

namespace App\Http\Controllers\Api;

class VisitaClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'visitas_clientes';
    protected string $inmueblesTable = 'visitas_inmuebles';
    protected string $historialTable = 'visitas_historial_acciones';
}
