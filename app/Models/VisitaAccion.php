<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaAccion extends Model
{
    protected $table = 'visita_acciones';

    protected $fillable = [
        'visita_id',
        'usuario_id',
        'fecha',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visita()
    {
        return $this->belongsTo(Visita::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
