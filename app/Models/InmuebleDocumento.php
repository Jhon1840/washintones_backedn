<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmuebleDocumento extends Model
{
    protected $table = 'inmuebles_documentos';

    protected $fillable = [
        'inmueble_id',
        'tipo_documento_id',
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
