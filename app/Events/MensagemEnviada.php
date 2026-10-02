<?php

namespace App\Events;

use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemEnviada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $idConversa;
    public int $idDestinatario;
    public array $mensagem;
    public array $remetente;
    public int $notificacoes;

    public function __construct(Mensagem $mensagem, User $remetente, User $destinatario)
    {
        $this->idConversa = $mensagem->id_conversa;
        $this->idDestinatario = $destinatario->id_usuario;
        $this->mensagem = [
            'id_mensagem' => $mensagem->id_mensagem,
            'id_conversa' => $mensagem->id_conversa,
            'mensagem' => $mensagem->mensagem,
            'id_remetente' => $mensagem->id_remetente,
            'id_destinatario' => $mensagem->id_destinatario,
            'data_envio' => $mensagem->data_envio->toIso8601String(),
            'hora' => $mensagem->data_envio->format('H:i'),
        ];
        $this->remetente = [
            'id_usuario' => $remetente->id_usuario,
            'nickname' => $remetente->nickname,
            'avatar' => $remetente->avatar ?: asset('images/icone.png'),
        ];
        $this->notificacoes = $destinatario->fresh()->totalNotificacoesChat();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversas.'.$this->idConversa),
            new PrivateChannel('usuarios.'.$this->idDestinatario),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mensagem.enviada';
    }

    public function broadcastWith(): array
    {
        return [
            'conversa' => $this->idConversa,
            'mensagem' => $this->mensagem,
            'remetente' => $this->remetente,
            'notificacoes' => $this->notificacoes,
        ];
    }
}
