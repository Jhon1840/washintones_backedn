<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaAccion extends Model
{
    protected $table = 'visita_acciones';

    protected $fillable = [
        'visita_id',
        'usuario_id',
        'fecha',
        'descripcion',
    ];
}
