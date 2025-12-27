<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Captacion extends Model
{
    protected $fillable = [
        'inmueble_id',
        'usuario_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];
}
