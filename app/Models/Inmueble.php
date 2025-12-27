<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inmueble extends Model
{
    protected $fillable = [
        'cliente_id',
        'zona',
        'estado',
        'tipo',
        'direccion',
        'precio',
        'moneda_id',
    ];
}
