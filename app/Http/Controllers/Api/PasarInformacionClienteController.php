<?php

namespace App\Http\Controllers\Api;

class PasarInformacionClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'pasar_informacion_clientes';
    protected string $inmueblesTable = 'pasar_informacion_inmuebles';
    protected string $historialTable = 'pasar_informacion_historial_acciones';
}
