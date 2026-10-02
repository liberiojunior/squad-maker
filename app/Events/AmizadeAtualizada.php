<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmizadeAtualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $idDestinatario;
    public string $acao;
    public ?int $idAmizade;
    public array $outroUsuario;
    public int $notificacoes;

    public function __construct(User $destinatario, User $outroUsuario, string $acao, ?int $idAmizade)
    {
        $this->idDestinatario = $destinatario->id_usuario;
        $this->acao = $acao;
        $this->idAmizade = $idAmizade;
        $this->outroUsuario = [
            'id_usuario' => $outroUsuario->id_usuario,
            'nickname' => $outroUsuario->nickname,
            'avatar' => $outroUsuario->avatar ?: asset('images/icone.png'),
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
        return 'amizade.atualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'acao' => $this->acao,
            'id_amizade' => $this->idAmizade,
            'usuario' => $this->outroUsuario,
            'notificacoes' => $this->notificacoes,
        ];
    }
}
