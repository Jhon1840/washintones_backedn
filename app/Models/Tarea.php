<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    protected $fillable = [
        'historial_id',
        'descripcion',
        'fecha',
        'tipo_id',
        'completado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'completado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
