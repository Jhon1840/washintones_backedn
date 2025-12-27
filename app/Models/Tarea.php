<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    protected $fillable = [
        'usuario_id',
        'titulo',
        'descripcion',
        'vence_en',
        'estado',
    ];
}
