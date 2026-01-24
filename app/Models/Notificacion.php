<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'titulo',
        'cuerpo',
        'tipo',
        'fuente',
        'fuente_id',
        'fecha_programada',
        'enviada_at',
        'leida_at',
        'data',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'enviada_at' => 'datetime',
        'leida_at' => 'datetime',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
