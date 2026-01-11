<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'duracion_dias',
        'precio',
        'activo',
    ];

    protected $casts = [
        'duracion_dias' => 'integer',
        'precio' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class);
    }
}
