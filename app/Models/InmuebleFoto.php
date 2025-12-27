<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleFoto extends Model
{
    protected $table = 'inmueble_fotos';

    protected $fillable = [
        'inmueble_id',
        'url',
        'descripcion',
    ];
}
