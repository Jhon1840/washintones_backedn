<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasarInformacion extends Model
{
    protected $table = 'pasar_informacion';

    protected $fillable = [
        'cliente_id',
        'inmueble_id',
        'usuario_id',
        'estado',
    ];
}
