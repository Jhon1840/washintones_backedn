<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Busqueda extends Model
{
    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'estado',
        'filtros',
    ];
}
