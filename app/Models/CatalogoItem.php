<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoItem extends Model
{
    protected $table = 'catalogo_items';

    protected $fillable = [
        'tipo',
        'clave',
        'valor',
        'orden',
    ];
}
