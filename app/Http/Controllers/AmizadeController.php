<?php

namespace App\Http\Controllers;

use App\Events\AmizadeAtualizada;
use App\Events\SolicitacaoAmizadeRecebida;
use App\Models\Amizade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AmizadeController extends Controller
{
    public function enviar(Request $request, User $user)
    {
        $usuario = $request->user();

        if ($usuario->id_usuario === $user->id_usuario) {
            abort(422, 'Você não pode enviar uma solicitação de amizade para si mesmo.');
        }

        $user->sincronizarStatusBanimento();

        if ($user->status_conta !== 'ativo') {
            throw ValidationException::withMessages([
                'amizade' => 'Não é possível enviar uma solicitação para esta conta.',
            ]);
        }

        [$idUsuario1, $idUsuario2] = $this->ordenarUsuarios($usuario, $user);

        $amizade = DB::transaction(function () use ($usuario, $idUsuario1, $idUsuario2) {
            DB::table('tb_usuario')
                ->whereIn('id_usuario', [$idUsuario1, $idUsuario2])
                ->orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            $existente = Amizade::where('id_usuario_1', $idUsuario1)
                ->where('id_usuario_2', $idUsuario2)
                ->lockForUpdate()
                ->first();

            if ($existente?->status_amizade === 'aceita') {
                throw ValidationException::withMessages([
                    'amizade' => 'Vocês já são amigos.',
                ]);
            }

            if ($existente?->status_amizade === 'pendente') {
                $mensagem = $existente->id_solicitante === $usuario->id_usuario
                    ? 'A solicitação de amizade já foi enviada.'
                    : 'Você já recebeu uma solicitação de amizade deste usuário.';

                throw ValidationException::withMessages([
                    'amizade' => $mensagem,
                ]);
            }

            return Amizade::create([
                'id_usuario_1' => $idUsuario1,
                'id_usuario_2' => $idUsuario2,
                'id_solicitante' => $usuario->id_usuario,
                'status_amizade' => 'pendente',
                'data_solicitacao' => now(),
                'data_resposta' => null,
            ]);
        });

        event(new SolicitacaoAmizadeRecebida($amizade, $usuario, $user));

        return back()->with('success', 'Solicitação de amizade enviada.');
    }

    public function aceitar(Request $request, Amizade $amizade)
    {
        $usuario = $request->user();

        $this->validarParticipante($amizade, $usuario);

        if ($amizade->status_amizade !== 'pendente'
            || $amizade->id_solicitante === $usuario->id_usuario) {
            throw ValidationException::withMessages([
                'amizade' => 'Esta solicitação não pode ser aceita.',
            ]);
        }

        $outroUsuario = $this->outroUsuario($amizade, $usuario);
        $outroUsuario->sincronizarStatusBanimento();

        if ($outroUsuario->status_conta !== 'ativo') {
            throw ValidationException::withMessages([
                'amizade' => 'Esta solicitação não pode ser aceita no momento.',
            ]);
        }

        $amizadeAtual = DB::transaction(function () use ($amizade, $usuario) {
            DB::table('tb_usuario')
                ->whereIn('id_usuario', [$amizade->id_usuario_1, $amizade->id_usuario_2])
                ->orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            $registro = Amizade::whereKey($amizade->id_amizade)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($registro, $usuario);

            if ($registro->status_amizade !== 'pendente'
                || $registro->id_solicitante === $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'amizade' => 'Esta solicitação não pode ser aceita.',
                ]);
            }

            $registro->update([
                'status_amizade' => 'aceita',
                'data_resposta' => now(),
            ]);

            return $registro->fresh();
        });

        event(new AmizadeAtualizada($usuario, $outroUsuario, 'aceita', $amizadeAtual->id_amizade));
        event(new AmizadeAtualizada($outroUsuario, $usuario, 'aceita', $amizadeAtual->id_amizade));

        return back()->with('success', 'Solicitação de amizade aceita.');
    }

    public function recusar(Request $request, Amizade $amizade)
    {
        $usuario = $request->user();

        $this->validarParticipante($amizade, $usuario);

        if ($amizade->status_amizade !== 'pendente'
            || $amizade->id_solicitante === $usuario->id_usuario) {
            throw ValidationException::withMessages([
                'amizade' => 'Esta solicitação não pode ser recusada.',
            ]);
        }

        $outroUsuario = $this->outroUsuario($amizade, $usuario);
        $idAmizade = $amizade->id_amizade;

        DB::transaction(function () use ($amizade, $usuario) {
            $registro = Amizade::whereKey($amizade->id_amizade)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($registro, $usuario);

            if ($registro->status_amizade !== 'pendente'
                || $registro->id_solicitante === $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'amizade' => 'Esta solicitação não pode ser recusada.',
                ]);
            }

            $registro->delete();
        });

        event(new AmizadeAtualizada($usuario, $outroUsuario, 'recusada', $idAmizade));
        event(new AmizadeAtualizada($outroUsuario, $usuario, 'recusada', $idAmizade));

        return back()->with('success', 'Solicitação de amizade recusada.');
    }

    public function cancelar(Request $request, Amizade $amizade)
    {
        $usuario = $request->user();

        $this->validarParticipante($amizade, $usuario);

        if ($amizade->status_amizade !== 'pendente'
            || $amizade->id_solicitante !== $usuario->id_usuario) {
            throw ValidationException::withMessages([
                'amizade' => 'Esta solicitação não pode ser cancelada.',
            ]);
        }

        $outroUsuario = $this->outroUsuario($amizade, $usuario);
        $idAmizade = $amizade->id_amizade;

        DB::transaction(function () use ($amizade, $usuario) {
            $registro = Amizade::whereKey($amizade->id_amizade)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($registro, $usuario);

            if ($registro->status_amizade !== 'pendente'
                || $registro->id_solicitante !== $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'amizade' => 'Esta solicitação não pode ser cancelada.',
                ]);
            }

            $registro->delete();
        });

        event(new AmizadeAtualizada($usuario, $outroUsuario, 'cancelada', $idAmizade));
        event(new AmizadeAtualizada($outroUsuario, $usuario, 'cancelada', $idAmizade));

        return back()->with('success', 'Solicitação de amizade cancelada.');
    }

    public function remover(Request $request, Amizade $amizade)
    {
        $usuario = $request->user();

        $this->validarParticipante($amizade, $usuario);

        if ($amizade->status_amizade !== 'aceita') {
            throw ValidationException::withMessages([
                'amizade' => 'Esta amizade não pode ser removida.',
            ]);
        }

        $outroUsuario = $this->outroUsuario($amizade, $usuario);
        $idAmizade = $amizade->id_amizade;

        DB::transaction(function () use ($amizade, $usuario) {
            $registro = Amizade::whereKey($amizade->id_amizade)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($registro, $usuario);

            if ($registro->status_amizade !== 'aceita') {
                throw ValidationException::withMessages([
                    'amizade' => 'Esta amizade não pode ser removida.',
                ]);
            }

            $registro->delete();
        });

        event(new AmizadeAtualizada($usuario, $outroUsuario, 'removida', $idAmizade));
        event(new AmizadeAtualizada($outroUsuario, $usuario, 'removida', $idAmizade));

        return back()->with('success', 'Amizade removida.');
    }

    private function ordenarUsuarios(User $usuario1, User $usuario2): array
    {
        return [
            min($usuario1->id_usuario, $usuario2->id_usuario),
            max($usuario1->id_usuario, $usuario2->id_usuario),
        ];
    }

    private function validarParticipante(Amizade $amizade, User $usuario): void
    {
        $participa = $amizade->id_usuario_1 === $usuario->id_usuario
            || $amizade->id_usuario_2 === $usuario->id_usuario;

        if (!$participa) {
            abort(404);
        }
    }

    private function outroUsuario(Amizade $amizade, User $usuario): User
    {
        $idOutroUsuario = $amizade->id_usuario_1 === $usuario->id_usuario
            ? $amizade->id_usuario_2
            : $amizade->id_usuario_1;

        return User::findOrFail($idOutroUsuario);
    }
}
