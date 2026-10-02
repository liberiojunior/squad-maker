<?php

namespace App\Events;

use App\Models\Amizade;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitacaoAmizadeRecebida implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $idDestinatario;
    public array $amizade;
    public array $solicitante;
    public int $notificacoes;

    public function __construct(Amizade $amizade, User $solicitante, User $destinatario)
    {
        $this->idDestinatario = $destinatario->id_usuario;
        $this->amizade = [
            'id_amizade' => $amizade->id_amizade,
            'data_solicitacao' => $amizade->data_solicitacao->toIso8601String(),
        ];
        $this->solicitante = [
            'id_usuario' => $solicitante->id_usuario,
            'nickname' => $solicitante->nickname,
            'avatar' => $solicitante->avatar ?: asset('images/icone.png'),
        ];
        $this->notificacoes = $destinatario->fresh()->totalNotificacoesChat();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('usuarios.'.$this->idDestinatario),
        ];
    }

    public function broadcastAs(): string
    {
        return 'amizade.solicitada';
    }

    public function broadcastWith(): array
    {
        return [
            'amizade' => $this->amizade,
            'solicitante' => $this->solicitante,
            'notificacoes' => $this->notificacoes,
        ];
    }
}
