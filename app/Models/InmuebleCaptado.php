<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleCaptado extends Model
{
    protected $table = 'inmuebles_captados';

    protected $fillable = [
        'inmueble_id',
        'captacion_id',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function captacion()
    {
        return $this->belongsTo(Captacion::class);
    }
}
