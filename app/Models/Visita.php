<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $fillable = [
        'inmueble_id',
        'cliente_id',
        'fecha',
        'estado',
    ];
}
