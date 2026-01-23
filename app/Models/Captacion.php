<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Captacion extends Model
{
    protected $table = 'captaciones';

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
