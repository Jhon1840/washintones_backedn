<?php

namespace App\Http\Controllers\Api;

class InmuebleCaptadoClienteController extends ModuleClienteControllerBase
{
    protected string $clientesTable = 'inmuebles_captados_clientes';
    protected string $inmueblesTable = 'inmuebles_captados_inmuebles';
    protected string $historialTable = 'inmuebles_captados_historial_acciones';
}
