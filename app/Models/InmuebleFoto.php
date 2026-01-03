<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleFoto extends Model
{
    protected $table = 'inmuebles_fotos';

    protected $fillable = [
        'inmueble_id',
        'url',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }
}
