<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Busqueda extends Model
{
    protected $table = 'busquedas_clientes';

    protected $fillable = [
        'cliente_id',
        'descripcion',
        'operacion_id',
        'tipo_inmueble_id',
        'zona_id',
        'presupuesto',
        'moneda_id',
    ];
}
