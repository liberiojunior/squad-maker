<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banimento extends Model
{
    protected $table = 'tb_banimento';

    protected $primaryKey = 'id_banimento';

    public $timestamps = false;

    protected $fillable = [
        'motivo',
        'data_inicio',
        'data_fim',
        'justificativa',
        'status_banimento',
        'id_administrador',
        'id_usuario_banido',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
    ];
}
