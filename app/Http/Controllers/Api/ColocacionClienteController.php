<?php

namespace App\Http\Controllers\Api;

class ColocacionClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'colocacion_clientes';
    protected string $inmueblesTable = 'colocacion_inmuebles';
    protected string $historialTable = 'colocacion_historial_acciones';
}
