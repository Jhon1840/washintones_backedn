<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasarInformacion extends Model
{
    protected $table = 'pasar_informacion';

    protected $fillable = [
        'cliente_id',
        'inmueble_id',
        'usuario_id',
        'estado',
        'comentarios',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
