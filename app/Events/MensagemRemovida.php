<?php

namespace App\Events;

use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemRemovida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $idMensagem;
    public int $idConversa;
    public int $idRemetente;
    public int $idDestinatario;
    public string $removidaEm;
    public int $notificacoesDestinatario;

    public function __construct(
        Mensagem $mensagem,
        User $destinatario
    ) {
        $this->idMensagem = $mensagem->id_mensagem;
        $this->idConversa = $mensagem->id_conversa;
        $this->idRemetente = $mensagem->id_remetente;
        $this->idDestinatario = $mensagem->id_destinatario;
        $this->removidaEm = $mensagem->removida_em->toIso8601String();
        $this->notificacoesDestinatario = $destinatario
            ->fresh()
            ->totalNotificacoesChat();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('usuarios.'.$this->idRemetente),
            new PrivateChannel('usuarios.'.$this->idDestinatario),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mensagem.removida';
    }

    public function broadcastWith(): array
    {
        return [
            'id_mensagem' => $this->idMensagem,
            'id_conversa' => $this->idConversa,
            'id_remetente' => $this->idRemetente,
            'id_destinatario' => $this->idDestinatario,
            'removida_em' => $this->removidaEm,
            'notificacoes_destinatario' => $this->notificacoesDestinatario,
        ];
    }
}
