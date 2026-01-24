<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = [
        'usuario_id',
        'token',
        'plataforma',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
