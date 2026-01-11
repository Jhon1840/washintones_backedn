<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';

    protected $fillable = [
        'usuario_id',
        'plan_id',
        'estado',
        'precio_mensual',
        'fecha_inicio',
        'fecha_fin',
        'ultimo_pago',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'precio_mensual' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'ultimo_pago' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
