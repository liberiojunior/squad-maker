<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversa extends Model
{
    protected $table = 'tb_conversa';

    protected $primaryKey = 'id_conversa';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario_1',
        'id_usuario_2',
        'data_criacao',
        'ultima_mensagem_em',
        'excluida_em_usuario_1',
        'excluida_em_usuario_2',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'ultima_mensagem_em' => 'datetime',
        'excluida_em_usuario_1' => 'datetime',
        'excluida_em_usuario_2' => 'datetime',
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

    public function mensagens()
    {
        return $this->hasMany(
            Mensagem::class,
            'id_conversa',
            'id_conversa'
        );
    }

    public function ultimaMensagem()
    {
        return $this->hasOne(
            Mensagem::class,
            'id_conversa',
            'id_conversa'
        )
            ->orderByDesc('data_envio')
            ->orderByDesc('id_mensagem');
    }
}
