<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    protected $table = 'tb_mensagem';

    protected $primaryKey = 'id_mensagem';

    public $timestamps = false;

    protected $fillable = [
        'id_conversa',
        'mensagem',
        'data_envio',
        'lida_em',
        'id_remetente',
        'id_destinatario',
    ];

    protected $casts = [
        'data_envio' => 'datetime',
        'lida_em' => 'datetime',
    ];

    public function conversa()
    {
        return $this->belongsTo(
            Conversa::class,
            'id_conversa',
            'id_conversa'
        );
    }

    public function remetente()
    {
        return $this->belongsTo(
            User::class,
            'id_remetente',
            'id_usuario'
        );
    }

    public function destinatario()
    {
        return $this->belongsTo(
            User::class,
            'id_destinatario',
            'id_usuario'
        );
    }
}
