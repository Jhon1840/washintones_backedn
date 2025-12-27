<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleDocumento extends Model
{
    protected $table = 'inmueble_documentos';

    protected $fillable = [
        'inmueble_id',
        'url',
        'tipo',
        'descripcion',
    ];
}
