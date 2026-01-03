<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colocacion extends Model
{
    protected $table = 'colocaciones';

    protected $fillable = [
        'busqueda_id',
        'inmueble_id',
        'asesor_id',
        'estado_id',
        'notas',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }
}
