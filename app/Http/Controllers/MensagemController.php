<?php

namespace App\Http\Controllers;

use App\Events\MensagemEnviada;
use App\Models\Amizade;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MensagemController extends Controller
{
    public function store(Request $request, Conversa $conversa)
    {
        $usuario = $request->user();

        $this->validarParticipante($conversa, $usuario);

        $data = $request->validate([
            'mensagem' => ['required', 'string', 'max:1000'],
        ], [
            'mensagem.required' => 'Digite uma mensagem.',
            'mensagem.max' => 'A mensagem pode ter no máximo 1000 caracteres.',
        ]);

        $texto = trim($data['mensagem']);

        if ($texto === '') {
            throw ValidationException::withMessages([
                'mensagem' => 'Digite uma mensagem.',
            ]);
        }

        $idDestinatario = $conversa->id_usuario_1 === $usuario->id_usuario
            ? $conversa->id_usuario_2
            : $conversa->id_usuario_1;

        $destinatario = User::findOrFail($idDestinatario);
        $destinatario->sincronizarStatusBanimento();

        if ($destinatario->status_conta !== 'ativo') {
            throw ValidationException::withMessages([
                'mensagem' => 'Não é possível enviar mensagens para esta conta no momento.',
            ]);
        }

        $idUsuario1 = min($usuario->id_usuario, $destinatario->id_usuario);
        $idUsuario2 = max($usuario->id_usuario, $destinatario->id_usuario);

        $amizadeExiste = Amizade::where('id_usuario_1', $idUsuario1)
            ->where('id_usuario_2', $idUsuario2)
            ->where('status_amizade', 'aceita')
            ->exists();

        if (!$amizadeExiste) {
            throw ValidationException::withMessages([
                'mensagem' => 'Vocês precisam ser amigos para continuar a conversa.',
            ]);
        }

        $mensagem = DB::transaction(function () use ($conversa, $usuario, $destinatario, $texto) {
            $conversaAtual = Conversa::whereKey($conversa->id_conversa)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($conversaAtual, $usuario);

            $agora = now();

            $mensagem = Mensagem::create([
                'id_conversa' => $conversaAtual->id_conversa,
                'mensagem' => $texto,
                'data_envio' => $agora,
                'lida_em' => null,
                'id_remetente' => $usuario->id_usuario,
                'id_destinatario' => $destinatario->id_usuario,
            ]);

            $conversaAtual->update([
                'ultima_mensagem_em' => $agora,
            ]);

            return $mensagem;
        });

        event(new MensagemEnviada($mensagem, $usuario, $destinatario));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Mensagem enviada.',
                'mensagem' => [
                    'id_mensagem' => $mensagem->id_mensagem,
                    'id_conversa' => $mensagem->id_conversa,
                    'mensagem' => $mensagem->mensagem,
                    'id_remetente' => $mensagem->id_remetente,
                    'id_destinatario' => $mensagem->id_destinatario,
                    'data_envio' => $mensagem->data_envio->toIso8601String(),
                    'hora' => $mensagem->data_envio->format('H:i'),
                ],
            ]);
        }

        return redirect()->route('conversas.show', $conversa);
    }

    public function marcarComoLidas(Request $request, Conversa $conversa)
    {
        $usuario = $request->user();

        $this->validarParticipante($conversa, $usuario);

        $conversa->mensagens()
            ->where('id_destinatario', $usuario->id_usuario)
            ->whereNull('lida_em')
            ->update([
                'lida_em' => now(),
            ]);

        return response()->json([
            'notificacoes' => $usuario->fresh()->totalNotificacoesChat(),
        ]);
    }

    private function validarParticipante(Conversa $conversa, User $usuario): void
    {
        $participa = $conversa->id_usuario_1 === $usuario->id_usuario
            || $conversa->id_usuario_2 === $usuario->id_usuario;

        if (!$participa) {
            abort(404);
        }
    }
}
