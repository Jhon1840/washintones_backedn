<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Historial extends Model
{
    protected $fillable = [
        'entidad',
        'referencia_id',
        'usuario_id',
        'fecha',
        'descripcion',
    ];
}
