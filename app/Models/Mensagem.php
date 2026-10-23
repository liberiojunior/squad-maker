<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    public const PRAZO_CANCELAMENTO_MINUTOS = 3;

    protected $table = 'tb_mensagem';

    protected $primaryKey = 'id_mensagem';

    public $timestamps = false;

    protected $fillable = [
        'id_conversa',
        'mensagem',
        'data_envio',
        'lida_em',
        'removida_em',
        'id_remetente',
        'id_destinatario',
    ];

    protected $casts = [
        'data_envio' => 'datetime',
        'lida_em' => 'datetime',
        'removida_em' => 'datetime',
    ];

    public function podeCancelarEnvio(): bool
    {
        if ($this->removida_em || ! $this->data_envio) {
            return false;
        }

        return now()->lessThanOrEqualTo(
            $this->data_envio->copy()->addMinutes(self::PRAZO_CANCELAMENTO_MINUTOS)
        );
    }

    public function cancelamentoExpiraEm()
    {
        if (! $this->data_envio) {
            return null;
        }

        return $this->data_envio
            ->copy()
            ->addMinutes(self::PRAZO_CANCELAMENTO_MINUTOS);
    }

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
