<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusquedaInmueble extends Model
{
    protected $table = 'busqueda_inmueble';

    protected $fillable = [
        'busqueda_id',
        'inmueble_id',
    ];
}
