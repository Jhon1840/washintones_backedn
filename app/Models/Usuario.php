<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'foto_url',
        'activo',
        'password',
        'es_admin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_admin' => 'boolean',
    ];

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }
}
