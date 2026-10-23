<?php

namespace App\Http\Controllers;

use App\Events\AmizadeAtualizada;
use App\Events\SolicitacaoAmizadeRecebida;
use App\Models\Amizade;
use App\Models\User;
use App\Models\UsuarioBloqueio;
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

        if ($this->existeBloqueioEntre($usuario, $user)) {
            throw ValidationException::withMessages([
                'amizade' => 'Não é possível enviar uma solicitação para este usuário.',
            ]);
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

            $bloqueado = UsuarioBloqueio::where(function ($query) use ($idUsuario1, $idUsuario2) {
                $query
                    ->where('id_bloqueador', $idUsuario1)
                    ->where('id_bloqueado', $idUsuario2);
            })
                ->orWhere(function ($query) use ($idUsuario1, $idUsuario2) {
                    $query
                        ->where('id_bloqueador', $idUsuario2)
                        ->where('id_bloqueado', $idUsuario1);
                })
                ->exists();

            if ($bloqueado) {
                throw ValidationException::withMessages([
                    'amizade' => 'Não é possível enviar uma solicitação para este usuário.',
                ]);
            }

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

        $this->transmitirEventos(
            new SolicitacaoAmizadeRecebida($amizade, $usuario, $user)
        );

        return $this->responderSucesso($request, 'Solicitação de amizade enviada.');
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

            $bloqueado = UsuarioBloqueio::where(function ($query) use ($amizade) {
                $query
                    ->where('id_bloqueador', $amizade->id_usuario_1)
                    ->where('id_bloqueado', $amizade->id_usuario_2);
            })
                ->orWhere(function ($query) use ($amizade) {
                    $query
                        ->where('id_bloqueador', $amizade->id_usuario_2)
                        ->where('id_bloqueado', $amizade->id_usuario_1);
                })
                ->exists();

            if ($bloqueado) {
                throw ValidationException::withMessages([
                    'amizade' => 'Esta solicitação não pode ser aceita.',
                ]);
            }

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

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $outroUsuario, 'aceita', $amizadeAtual->id_amizade),
            new AmizadeAtualizada($outroUsuario, $usuario, 'aceita', $amizadeAtual->id_amizade)
        );

        return $this->responderSucesso($request, 'Solicitação de amizade aceita.');
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

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $outroUsuario, 'recusada', $idAmizade),
            new AmizadeAtualizada($outroUsuario, $usuario, 'recusada', $idAmizade)
        );

        return $this->responderSucesso($request, 'Solicitação de amizade recusada.');
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

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $outroUsuario, 'cancelada', $idAmizade),
            new AmizadeAtualizada($outroUsuario, $usuario, 'cancelada', $idAmizade)
        );

        return $this->responderSucesso($request, 'Solicitação de amizade cancelada.');
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

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $outroUsuario, 'removida', $idAmizade),
            new AmizadeAtualizada($outroUsuario, $usuario, 'removida', $idAmizade)
        );

        return $this->responderSucesso($request, 'Amizade removida.');
    }

    public function bloquear(Request $request, User $user)
    {
        $usuario = $request->user();

        if ($usuario->id_usuario === $user->id_usuario) {
            abort(422);
        }

        DB::transaction(function () use ($usuario, $user) {
            DB::table('tb_usuario')
                ->whereIn('id_usuario', [
                    $usuario->id_usuario,
                    $user->id_usuario,
                ])
                ->orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            UsuarioBloqueio::firstOrCreate(
                [
                    'id_bloqueador' => $usuario->id_usuario,
                    'id_bloqueado' => $user->id_usuario,
                ],
                [
                    'data_bloqueio' => now(),
                ]
            );

            [$idUsuario1, $idUsuario2] = $this->ordenarUsuarios(
                $usuario,
                $user
            );

            Amizade::where('id_usuario_1', $idUsuario1)
                ->where('id_usuario_2', $idUsuario2)
                ->delete();

            DB::table('tb_mensagem')
                ->whereNull('lida_em')
                ->where(function ($query) use ($usuario, $user) {
                    $query
                        ->where(function ($subquery) use ($usuario, $user) {
                            $subquery
                                ->where('id_remetente', $user->id_usuario)
                                ->where('id_destinatario', $usuario->id_usuario);
                        })
                        ->orWhere(function ($subquery) use ($usuario, $user) {
                            $subquery
                                ->where('id_remetente', $usuario->id_usuario)
                                ->where('id_destinatario', $user->id_usuario);
                        });
                })
                ->update([
                    'lida_em' => now(),
                ]);
        });

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $user, 'bloqueada', null),
            new AmizadeAtualizada($user, $usuario, 'bloqueada', null)
        );

        return $this->responderSucesso(
            $request,
            'Usuário bloqueado.'
        );
    }

    public function desbloquear(Request $request, User $user)
    {
        $usuario = $request->user();

        UsuarioBloqueio::where(
            'id_bloqueador',
            $usuario->id_usuario
        )
            ->where('id_bloqueado', $user->id_usuario)
            ->delete();

        $this->transmitirEventos(
            new AmizadeAtualizada($usuario, $user, 'desbloqueada', null),
            new AmizadeAtualizada($user, $usuario, 'desbloqueada', null)
        );

        return $this->responderSucesso(
            $request,
            'Usuário desbloqueado.'
        );
    }

    private function existeBloqueioEntre(
        User $usuario1,
        User $usuario2
    ): bool {
        return UsuarioBloqueio::where(function ($query) use ($usuario1, $usuario2) {
            $query
                ->where('id_bloqueador', $usuario1->id_usuario)
                ->where('id_bloqueado', $usuario2->id_usuario);
        })
            ->orWhere(function ($query) use ($usuario1, $usuario2) {
                $query
                    ->where('id_bloqueador', $usuario2->id_usuario)
                    ->where('id_bloqueado', $usuario1->id_usuario);
            })
            ->exists();
    }

    private function transmitirEventos(object ...$eventos): void
    {
        foreach ($eventos as $evento) {
            try {
                event($evento);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function responderSucesso(Request $request, string $mensagem)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensagem]);
        }

        return back()->with('success', $mensagem);
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
