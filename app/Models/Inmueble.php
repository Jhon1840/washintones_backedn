<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inmueble extends Model
{
    protected $fillable = [
        'cliente_id',
        'direccion',
        'descripcion',
        'tipo_id',
        'zona_id',
        'operacion_id',
        'amc_estado_id',
        'valor_estimado',
        'moneda_id',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fotos()
    {
        return $this->hasMany(InmuebleFoto::class);
    }

    public function documentos()
    {
        return $this->hasMany(InmuebleDocumento::class);
    }
}
