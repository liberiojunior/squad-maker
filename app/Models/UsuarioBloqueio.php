<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioBloqueio extends Model
{
    protected $table = 'tb_usuario_bloqueio';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_bloqueador',
        'id_bloqueado',
        'data_bloqueio',
    ];

    protected $casts = [
        'data_bloqueio' => 'datetime',
    ];

    public function bloqueador()
    {
        return $this->belongsTo(
            User::class,
            'id_bloqueador',
            'id_usuario'
        );
    }

    public function bloqueado()
    {
        return $this->belongsTo(
            User::class,
            'id_bloqueado',
            'id_usuario'
        );
    }
}
