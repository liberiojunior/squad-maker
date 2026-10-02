<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amizade extends Model
{
    protected $table = 'tb_amizade';

    protected $primaryKey = 'id_amizade';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario_1',
        'id_usuario_2',
        'id_solicitante',
        'status_amizade',
        'data_solicitacao',
        'data_resposta',
    ];

    protected $casts = [
        'data_solicitacao' => 'datetime',
        'data_resposta' => 'datetime',
    ];

    public function usuario1()
    {
        return $this->belongsTo(
            User::class,
            'id_usuario_1',
            'id_usuario'
        );
    }

    public function usuario2()
    {
        return $this->belongsTo(
            User::class,
            'id_usuario_2',
            'id_usuario'
        );
    }

    public function solicitante()
    {
        return $this->belongsTo(
            User::class,
            'id_solicitante',
            'id_usuario'
        );
    }
}
