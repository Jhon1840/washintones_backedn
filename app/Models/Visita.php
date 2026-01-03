<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $fillable = [
        'inmueble_id',
        'cliente_id',
        'fecha',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function acciones()
    {
        return $this->hasMany(VisitaAccion::class);
    }
}
