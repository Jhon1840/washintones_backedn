<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interesado extends Model
{
    protected $fillable = [
        'cliente_id',
        'nombre',
        'email',
        'telefono',
        'estado',
    ];
}
