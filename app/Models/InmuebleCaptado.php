<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleCaptado extends Model
{
    protected $table = 'inmuebles_captados';

    protected $fillable = [
        'inmueble_id',
        'captacion_id',
        'estado',
    ];
}
