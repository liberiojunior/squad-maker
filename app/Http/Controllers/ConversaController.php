<?php

namespace App\Http\Controllers;

use App\Models\Amizade;
use App\Models\Conversa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversaController extends Controller
{
    public function index(Request $request)
    {
        return view('conversas.index', $this->dadosDaTela($request->user()));
    }

    public function iniciar(Request $request, User $user)
    {
        $usuario = $request->user();

        if ($usuario->id_usuario === $user->id_usuario) {
            abort(404);
        }

        $user->sincronizarStatusBanimento();

        if ($user->status_conta !== 'ativo') {
            throw ValidationException::withMessages([
                'conversa' => 'Não é possível iniciar uma conversa com esta conta.',
            ]);
        }

        [$idUsuario1, $idUsuario2] = $this->ordenarUsuarios($usuario, $user);

        $amizadeExiste = Amizade::where('id_usuario_1', $idUsuario1)
            ->where('id_usuario_2', $idUsuario2)
            ->where('status_amizade', 'aceita')
            ->exists();

        if (!$amizadeExiste) {
            throw ValidationException::withMessages([
                'conversa' => 'Só é possível conversar com usuários que são seus amigos.',
            ]);
        }

        $conversa = DB::transaction(function () use ($idUsuario1, $idUsuario2) {
            DB::table('tb_usuario')
                ->whereIn('id_usuario', [$idUsuario1, $idUsuario2])
                ->orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            $existente = Conversa::where('id_usuario_1', $idUsuario1)
                ->where('id_usuario_2', $idUsuario2)
                ->lockForUpdate()
                ->first();

            if ($existente) {
                return $existente;
            }

            return Conversa::create([
                'id_usuario_1' => $idUsuario1,
                'id_usuario_2' => $idUsuario2,
                'data_criacao' => now(),
                'ultima_mensagem_em' => null,
            ]);
        });

        return redirect()->route('conversas.show', $conversa);
    }

    public function show(Request $request, Conversa $conversa)
    {
        $usuario = $request->user();

        $this->validarParticipante($conversa, $usuario);

        $dataExclusao = $this->dataExclusaoDaConversa($conversa, $usuario);

        $mensagensRecebidas = $conversa->mensagens()
            ->where('id_destinatario', $usuario->id_usuario)
            ->whereNull('lida_em');

        if ($dataExclusao) {
            $mensagensRecebidas->where('data_envio', '>', $dataExclusao);
        }

        $mensagensRecebidas->update([
            'lida_em' => now(),
        ]);

        $conversa->load([
            'usuario1',
            'usuario2',
            'mensagens' => function ($query) use ($dataExclusao) {
                if ($dataExclusao) {
                    $query->where('data_envio', '>', $dataExclusao);
                }

                $query
                    ->orderBy('data_envio')
                    ->orderBy('id_mensagem');
            },
        ]);

        return view('conversas.index', $this->dadosDaTela($usuario, $conversa));
    }

    public function destroy(Request $request, Conversa $conversa)
    {
        $usuario = $request->user();

        $this->validarParticipante($conversa, $usuario);

        DB::transaction(function () use ($conversa, $usuario) {
            $conversaAtual = Conversa::whereKey($conversa->id_conversa)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarParticipante($conversaAtual, $usuario);

            $agora = now();
            $coluna = $this->colunaExclusaoDaConversa($conversaAtual, $usuario);

            $conversaAtual->update([
                $coluna => $agora,
            ]);

            $conversaAtual->mensagens()
                ->where('id_destinatario', $usuario->id_usuario)
                ->whereNull('lida_em')
                ->update([
                    'lida_em' => $agora,
                ]);
        });

        return redirect()
            ->route('conversas.index')
            ->with('success', 'Conversa removida da sua lista.');
    }

    private function dadosDaTela(User $usuario, ?Conversa $conversaAtual = null): array
    {
        $idUsuario = $usuario->id_usuario;

        $solicitacoes = Amizade::with(['usuario1', 'usuario2'])
            ->where('status_amizade', 'pendente')
            ->where('id_solicitante', '!=', $idUsuario)
            ->where(function ($query) use ($idUsuario) {
                $query
                    ->where('id_usuario_1', $idUsuario)
                    ->orWhere('id_usuario_2', $idUsuario);
            })
            ->orderByDesc('data_solicitacao')
            ->get()
            ->map(function (Amizade $amizade) use ($idUsuario) {
                $outroUsuario = $amizade->id_usuario_1 === $idUsuario
                    ? $amizade->usuario2
                    : $amizade->usuario1;

                $outroUsuario->sincronizarStatusBanimento();

                return [
                    'amizade' => $amizade,
                    'usuario' => $outroUsuario,
                    'presenca' => $outroUsuario->presenca(),
                ];
            })
            ->filter(function (array $item) {
                return $item['usuario']->status_conta === 'ativo';
            })
            ->values();

        $conversas = Conversa::with(['usuario1', 'usuario2', 'ultimaMensagem'])
            ->withCount([
                'mensagens as nao_lidas' => function ($query) use ($idUsuario) {
                    $query
                        ->where('id_destinatario', $idUsuario)
                        ->whereNull('lida_em');
                },
            ])
            ->where(function ($query) use ($idUsuario) {
                $query
                    ->where(function ($subquery) use ($idUsuario) {
                        $subquery
                            ->where('id_usuario_1', $idUsuario)
                            ->where(function ($visivel) {
                                $visivel
                                    ->whereNull('excluida_em_usuario_1')
                                    ->orWhereColumn('ultima_mensagem_em', '>', 'excluida_em_usuario_1');
                            });
                    })
                    ->orWhere(function ($subquery) use ($idUsuario) {
                        $subquery
                            ->where('id_usuario_2', $idUsuario)
                            ->where(function ($visivel) {
                                $visivel
                                    ->whereNull('excluida_em_usuario_2')
                                    ->orWhereColumn('ultima_mensagem_em', '>', 'excluida_em_usuario_2');
                            });
                    });
            })
            ->orderByRaw('ultima_mensagem_em IS NULL')
            ->orderByDesc('ultima_mensagem_em')
            ->orderByDesc('data_criacao')
            ->get()
            ->map(function (Conversa $conversa) use ($idUsuario) {
                $outroUsuario = $conversa->id_usuario_1 === $idUsuario
                    ? $conversa->usuario2
                    : $conversa->usuario1;

                $outroUsuario->sincronizarStatusBanimento();

                return [
                    'conversa' => $conversa,
                    'usuario' => $outroUsuario,
                    'presenca' => $outroUsuario->presenca(),
                    'ultima_mensagem' => $conversa->ultimaMensagem,
                    'nao_lidas' => (int)$conversa->nao_lidas,
                ];
            });

        $amigos = Amizade::with(['usuario1', 'usuario2'])
            ->where('status_amizade', 'aceita')
            ->where(function ($query) use ($idUsuario) {
                $query
                    ->where('id_usuario_1', $idUsuario)
                    ->orWhere('id_usuario_2', $idUsuario);
            })
            ->get()
            ->map(function (Amizade $amizade) use ($idUsuario) {
                $outroUsuario = $amizade->id_usuario_1 === $idUsuario
                    ? $amizade->usuario2
                    : $amizade->usuario1;

                $outroUsuario->sincronizarStatusBanimento();

                return [
                    'amizade' => $amizade,
                    'usuario' => $outroUsuario,
                    'presenca' => $outroUsuario->presenca(),
                ];
            })
            ->filter(function (array $item) {
                return $item['usuario']->status_conta === 'ativo';
            })
            ->sort(function (array $itemA, array $itemB) {
                $ordem = [
                    'online' => 0,
                    'recente' => 1,
                    'offline' => 2,
                ];

                $statusA = $ordem[$itemA['presenca']['status']] ?? 3;
                $statusB = $ordem[$itemB['presenca']['status']] ?? 3;

                if ($statusA !== $statusB) {
                    return $statusA <=> $statusB;
                }

                $atividadeA = $itemA['usuario']->ultima_atividade?->timestamp ?? 0;
                $atividadeB = $itemB['usuario']->ultima_atividade?->timestamp ?? 0;

                if ($atividadeA !== $atividadeB) {
                    return $atividadeB <=> $atividadeA;
                }

                return strcasecmp(
                    $itemA['usuario']->nickname,
                    $itemB['usuario']->nickname
                );
            })
            ->values();

        $dicas = [
            [
                'titulo' => 'Encontre quem joga do seu jeito.',
                'texto' => 'Confira jogos, plataformas e níveis para encontrar parceiros mais compatíveis.',
                'imagem' => 'images/chat/dicas/dica-1.png',
            ],
            [
                'titulo' => 'Uma boa squad começa na conversa.',
                'texto' => 'Troque uma ideia antes da partida e veja se o estilo de vocês combina.',
                'imagem' => 'images/chat/dicas/dica-2.png',
            ],
            [
                'titulo' => 'Combine antes de jogar.',
                'texto' => 'Alinhe horário, plataforma e objetivo da partida para todo mundo entrar na mesma sintonia.',
                'imagem' => 'images/chat/dicas/dica-3.png',
            ],
            [
                'titulo' => 'Cada jogador tem seu ritmo.',
                'texto' => 'Respeite o jeito de jogar de quem está na sua squad e aproveite melhor a partida.',
                'imagem' => 'images/chat/dicas/dica-4.png',
            ],
            [
                'titulo' => 'Mantenha seu perfil atualizado.',
                'texto' => 'Seus jogos, plataformas e níveis ajudam outros jogadores a conhecer melhor seu estilo.',
                'imagem' => 'images/chat/dicas/dica-5.png',
            ],
        ];

        $dica = $dicas[array_rand($dicas)];
        $outroUsuarioAtual = null;
        $presencaAtual = null;
        $podeEnviar = false;

        if ($conversaAtual) {
            $outroUsuarioAtual = $conversaAtual->id_usuario_1 === $idUsuario
                ? $conversaAtual->usuario2
                : $conversaAtual->usuario1;

            $outroUsuarioAtual->sincronizarStatusBanimento();
            $presencaAtual = $outroUsuarioAtual->presenca();

            $idUsuario1 = min($idUsuario, $outroUsuarioAtual->id_usuario);
            $idUsuario2 = max($idUsuario, $outroUsuarioAtual->id_usuario);

            $podeEnviar = $outroUsuarioAtual->status_conta === 'ativo'
                && Amizade::where('id_usuario_1', $idUsuario1)
                    ->where('id_usuario_2', $idUsuario2)
                    ->where('status_amizade', 'aceita')
                    ->exists();
        }

        return [
            'solicitacoes' => $solicitacoes,
            'conversas' => $conversas,
            'amigos' => $amigos,
            'conversaAtual' => $conversaAtual,
            'outroUsuarioAtual' => $outroUsuarioAtual,
            'presencaAtual' => $presencaAtual,
            'podeEnviar' => $podeEnviar,
            'dica' => $dica,
        ];
    }

    private function ordenarUsuarios(User $usuario1, User $usuario2): array
    {
        return [
            min($usuario1->id_usuario, $usuario2->id_usuario),
            max($usuario1->id_usuario, $usuario2->id_usuario),
        ];
    }

    private function colunaExclusaoDaConversa(Conversa $conversa, User $usuario): string
    {
        return $conversa->id_usuario_1 === $usuario->id_usuario
            ? 'excluida_em_usuario_1'
            : 'excluida_em_usuario_2';
    }

    private function dataExclusaoDaConversa(Conversa $conversa, User $usuario)
    {
        $coluna = $this->colunaExclusaoDaConversa($conversa, $usuario);

        return $conversa->{$coluna};
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
