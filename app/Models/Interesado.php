<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interesado extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
