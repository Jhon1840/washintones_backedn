<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colocacion extends Model
{
    protected $fillable = [
        'busqueda_id',
        'inmueble_id',
        'estado',
    ];
}
