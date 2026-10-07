<?php

namespace App\Http\Controllers;

use App\Models\Amizade;
use App\Models\Genero;
use App\Models\Jogo;
use App\Models\Plataforma;
use App\Models\User;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $user->load([
            'generos' => function ($query) {
                $query->orderBy('genero');
            },

            'jogos' => function ($query) {
                $query
                    ->orderByRaw('tb_jogo_usuario.ordem_perfil IS NULL')
                    ->orderBy('tb_jogo_usuario.ordem_perfil')
                    ->orderBy('tb_jogo_usuario.data_adicao');
            },

            'plataformas' => function ($query) {
                $query
                    ->orderByRaw('tb_usuario_plataforma.ordem_perfil IS NULL')
                    ->orderBy('tb_usuario_plataforma.ordem_perfil')
                    ->orderBy('tb_usuario_plataforma.data_adicao')
                    ->orderBy('tb_plataforma.nome');
            },
        ]);

        $generos = Genero::orderBy('genero')->get();
        $plataformasDisponiveis = Plataforma::orderBy('nome')->get();

        $niveisJogosUsuario = $user->jogos
            ->mapWithKeys(function ($jogo) {
                return [
                    $jogo->id_jogo => $jogo->pivot->nivel_proficiencia,
                ];
            })
            ->toArray();

        $posts = $user->posts()
            ->where('status_post', 'ativo')
            ->withCount('reacoes')
            ->orderByRaw('fixado_em IS NULL')
            ->orderByDesc('fixado_em')
            ->orderByDesc('data_publicacao')
            ->orderByDesc('id_post')
            ->limit(5)
            ->get();

        $postsReagidos = [];

        if ($posts->isNotEmpty()) {
            $postsReagidos = DB::table('tb_post_reacao')
                ->where('id_usuario', $user->id_usuario)
                ->whereIn('id_post', $posts->pluck('id_post'))
                ->pluck('id_post')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $totalPosts = $user->posts()
            ->where('status_post', 'ativo')
            ->count();

        return view('perfil', [
            'user' => $user,
            'generos' => $generos,
            'plataformasDisponiveis' => $plataformasDisponiveis,
            'niveis' => NivelProficiencia::todos(),
            'descricoesNiveis' => NivelProficiencia::descricoesSelecao(),
            'niveisJogosUsuario' => $niveisJogosUsuario,
            'posts' => $posts,
            'totalPosts' => $totalPosts,
            'postsReagidos' => $postsReagidos,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nickname' => [
                'required',
                'string',
                'max:80',
                'regex:/^[^\/?#\\\\]+$/u',
                Rule::unique('tb_usuario', 'nickname')->ignore(
                    $user->id_usuario,
                    'id_usuario'
                ),
            ],
            'bio' => ['nullable', 'string', 'max:400'],
        ], [
            'nickname.regex' => 'O nome não pode conter /, \\, ? ou #.',
            'nickname.unique' => 'Este nome de perfil já está em uso.',
            'bio.max' => 'A bio pode ter no máximo 400 caracteres.',
        ]);

        $user->nickname = $data['nickname'];
        $user->bio = $data['bio'] ?? null;
        $user->save();

        return redirect()
            ->route('perfil')
            ->with('success', 'Perfil atualizado.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');
        $pasta = public_path('uploads/avatars');
        $avatarAnterior = $user->avatar;

        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        $fileName = 'avatar_' . $user->id_usuario . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($pasta, $fileName);

        $user->avatar = '/uploads/avatars/' . $fileName;
        $user->save();

        if ($avatarAnterior && str_starts_with($avatarAnterior, '/uploads/avatars/')) {
            $arquivoAnterior = public_path(ltrim($avatarAnterior, '/'));

            if (is_file($arquivoAnterior)) {
                unlink($arquivoAnterior);
            }
        }

        return redirect()->route('perfil')->with('success', 'Foto atualizada.');
    }

    public function updateGeneros(Request $request)
    {
        $data = $request->validate([
            'generos' => ['nullable', 'array', 'max:5'],

            'generos.*' => ['integer', 'distinct', 'exists:tb_genero,id_genero'],
        ]);

        $request->user()
            ->generos()
            ->sync(
                $data['generos'] ?? []
            );

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Gêneros atualizados.'
            );
    }

    public function buscarJogos(Request $request)
    {
        $data = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $termo = trim($data['q'] ?? '');

        if ($termo === '') {
            return response()->json([
                'jogos' => [],
            ]);
        }

        $user = $request->user();

        $jogosUsuario = $user
            ->jogos()
            ->get()
            ->keyBy('id_jogo');

        $jogos = Jogo::query()
            ->where('nome', 'like', '%' . $termo . '%')
            ->orderBy('nome')
            ->limit(12)
            ->get([
                'id_jogo',
                'nome',
                'capa',
            ])
            ->map(function ($jogo) use ($jogosUsuario) {
                $jogoUsuario = $jogosUsuario->get(
                    $jogo->id_jogo
                );

                return [
                    'id_jogo' => $jogo->id_jogo,
                    'nome' => $jogo->nome,
                    'capa' => $jogo->capa,
                    'selecionado' => $jogoUsuario !== null,
                    'nivel_proficiencia' => $jogoUsuario
                        ? $jogoUsuario->pivot->nivel_proficiencia
                        : null,
                    'ordem_perfil' => $jogoUsuario
                        ? $jogoUsuario->pivot->ordem_perfil
                        : null,
                ];
            })
            ->values();

        return response()->json([
            'jogos' => $jogos,
        ]);
    }

    public function addJogo(
        Request $request,
        Jogo    $jogo
    )
    {
        $data = $request->validate([
            'nivel' => [
                'required',
                'integer',
                'between:1,5',
            ],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $jogo, $data) {
            DB::table('tb_usuario')
                ->where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->first();

            $jogosAtuais = DB::table('tb_jogo_usuario')
                ->where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->get([
                    'id_jogo',
                    'ordem_perfil',
                ]);

            $jaPossui = $jogosAtuais->contains(
                'id_jogo',
                $jogo->id_jogo
            );

            if ($jaPossui) {
                throw ValidationException::withMessages([
                    'jogo' => 'Este jogo já está em Meus Jogos.',
                ]);
            }

            $maiorOrdem = (int)($jogosAtuais->max('ordem_perfil') ?? 0);

            $user->jogos()->attach(
                $jogo->id_jogo,
                [
                    'data_adicao' => now(),
                    'nivel_proficiencia' => (int)$data['nivel'],
                    'ordem_perfil' => $maiorOrdem + 1,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Jogo adicionado ao perfil.',
            'nivel' => (int)$data['nivel'],
            'nivel_nome' => NivelProficiencia::nome(
                (int)$data['nivel']
            ),
        ]);
    }

    public function updateJogos(Request $request)
    {
        $data = $request->validate([
            'jogos' => [
                'nullable',
                'array',
            ],
            'jogos.*' => [
                'integer',
                'distinct',
                'exists:tb_jogo,id_jogo',
            ],
            'niveis' => [
                'nullable',
                'array',
            ],
            'niveis.*' => [
                'nullable',
                'integer',
                'between:1,5',
            ],
        ]);

        $user = $request->user();
        $jogosSelecionados = $data['jogos'] ?? [];
        $niveis = $data['niveis'] ?? [];

        $jogosAtuais = $user
            ->jogos()
            ->get()
            ->keyBy('id_jogo');

        $maiorOrdem = (int)$user
            ->jogos()
            ->max('tb_jogo_usuario.ordem_perfil');

        $sincronizar = [];

        foreach ($jogosSelecionados as $idJogo) {
            $idJogo = (int)$idJogo;
            $jogoAtual = $jogosAtuais->get($idJogo);
            $nivel = $niveis[$idJogo] ?? null;

            if (!$jogoAtual && !$nivel) {
                throw ValidationException::withMessages([
                    'jogos' => 'Defina o nível de proficiência do novo jogo.',
                ]);
            }

            if (!$nivel && $jogoAtual) {
                $nivel = $jogoAtual
                    ->pivot
                    ->nivel_proficiencia;
            }

            if ($jogoAtual) {
                $ordem = $jogoAtual
                    ->pivot
                    ->ordem_perfil;
            } else {
                $maiorOrdem++;
                $ordem = $maiorOrdem;
            }

            $sincronizar[$idJogo] = [
                'data_adicao' => $jogoAtual
                    ? $jogoAtual->pivot->data_adicao
                    : now(),
                'nivel_proficiencia' => $nivel,
                'ordem_perfil' => $ordem,
            ];
        }

        $user->jogos()->sync($sincronizar);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jogos atualizados.',
            ]);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Jogos atualizados.'
            );
    }

    public function updateNivelJogo(
        Request $request,
        Jogo    $jogo
    )
    {
        $data = $request->validate([
            'nivel' => [
                'required',
                'integer',
                'between:1,5',
            ],
        ]);

        $user = $request->user();

        $possuiJogo = $user
            ->jogos()
            ->where(
                'tb_jogo.id_jogo',
                $jogo->id_jogo
            )
            ->exists();

        if (!$possuiJogo) {
            abort(404);
        }

        $user
            ->jogos()
            ->updateExistingPivot(
                $jogo->id_jogo,
                [
                    'nivel_proficiencia' => $data['nivel'],
                ]
            );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Nível do jogo atualizado.',
                'nivel' => (int)$data['nivel'],
                'nivel_nome' => NivelProficiencia::nome(
                    (int)$data['nivel']
                ),
            ]);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Nível do jogo atualizado.'
            );
    }

    public function removeJogo(
        Request $request,
        Jogo    $jogo
    )
    {
        $user = $request->user();

        $possuiJogo = $user
            ->jogos()
            ->where(
                'tb_jogo.id_jogo',
                $jogo->id_jogo
            )
            ->exists();

        if (!$possuiJogo) {
            abort(404);
        }

        $user->jogos()->detach(
            $jogo->id_jogo
        );

        $this->reorganizarOrdemJogos($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jogo removido do perfil.',
            ]);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Jogo removido do perfil.'
            );
    }

    public function updateOrdemJogos(Request $request)
    {
        $data = $request->validate([
            'jogos' => [
                'required',
                'array',
            ],
            'jogos.*' => [
                'integer',
                'distinct',
                'exists:tb_jogo,id_jogo',
            ],
        ]);

        $user = $request->user();

        $idsAtuais = $user
            ->jogos()
            ->pluck('tb_jogo.id_jogo')
            ->map(fn($id) => (int)$id)
            ->sort()
            ->values()
            ->toArray();

        $idsRecebidos = collect($data['jogos'])
            ->map(fn($id) => (int)$id)
            ->sort()
            ->values()
            ->toArray();

        if ($idsAtuais !== $idsRecebidos) {
            throw ValidationException::withMessages([
                'jogos' => 'A lista de jogos enviada é inválida.',
            ]);
        }

        DB::transaction(function () use ($user, $data) {
            foreach ($data['jogos'] as $indice => $idJogo) {
                $user
                    ->jogos()
                    ->updateExistingPivot(
                        (int)$idJogo,
                        [
                            'ordem_perfil' => $indice + 1,
                        ]
                    );
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ordem dos jogos atualizada.',
            ]);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Ordem dos jogos atualizada.'
            );
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'confirmacao' => ['required', 'string'],
        ]);

        if ($request->confirmacao !== $user->nickname) {
            return back()->withErrors([
                'confirmacao' => 'O nome digitado não corresponde ao seu usuário.',
            ]);
        }

        $user->excluirConta();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Sua conta foi excluída.');
    }

    public function updatePlataformas(Request $request)
    {
        $data = $request->validate([
            'plataformas' => ['nullable', 'array'],
            'plataformas.*' => [
                'integer',
                'distinct',
                'exists:tb_plataforma,id_plataforma',
            ],
            'ordem_plataformas' => ['nullable', 'array'],
            'ordem_plataformas.*' => [
                'integer',
                'distinct',
                'exists:tb_plataforma,id_plataforma',
            ],
        ]);

        $user = $request->user();

        $selecionadas = array_values(array_unique(array_map(
            'intval',
            $data['plataformas'] ?? []
        )));

        $ordemRecebida = array_values(array_unique(array_map(
            'intval',
            $data['ordem_plataformas'] ?? []
        )));

        $atuais = $user
            ->plataformas()
            ->pluck('tb_plataforma.id_plataforma')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use (
            $user,
            $selecionadas,
            $ordemRecebida,
            $atuais
        ) {
            $adicionar = array_diff($selecionadas, $atuais);
            $remover = array_diff($atuais, $selecionadas);

            if (! empty($remover)) {
                $user->plataformas()->detach($remover);
            }

            foreach ($adicionar as $idPlataforma) {
                $user->plataformas()->attach(
                    $idPlataforma,
                    [
                        'data_adicao' => now(),
                        'ordem_perfil' => null,
                    ]
                );
            }

            $ordemFinal = array_values(array_filter(
                $ordemRecebida,
                fn ($id) => in_array($id, $selecionadas, true)
            ));

            foreach ($selecionadas as $idPlataforma) {
                if (! in_array($idPlataforma, $ordemFinal, true)) {
                    $ordemFinal[] = $idPlataforma;
                }
            }

            foreach ($ordemFinal as $indice => $idPlataforma) {
                $user->plataformas()->updateExistingPivot(
                    $idPlataforma,
                    [
                        'ordem_perfil' => $indice + 1,
                    ]
                );
            }
        });

        return redirect()
            ->route('perfil')
            ->with('success', 'Plataformas atualizadas.');
    }

    private function reorganizarOrdemJogos($user): void
    {
        $jogos = $user
            ->jogos()
            ->orderByRaw(
                'tb_jogo_usuario.ordem_perfil IS NULL'
            )
            ->orderBy(
                'tb_jogo_usuario.ordem_perfil'
            )
            ->orderBy(
                'tb_jogo_usuario.data_adicao'
            )
            ->get();

        DB::transaction(function () use ($user, $jogos) {
            foreach ($jogos as $indice => $jogo) {
                $user
                    ->jogos()
                    ->updateExistingPivot(
                        $jogo->id_jogo,
                        [
                            'ordem_perfil' => $indice + 1,
                        ]
                    );
            }
        });
    }

    public function showPublic(Request $request, User $user)
    {
        $usuarioAtual = $request->user();

        if ($user->id_usuario === $usuarioAtual->id_usuario) {
            return redirect()->route('perfil');
        }

        $user->sincronizarStatusBanimento();

        if (!in_array($user->status_conta, ['ativo', 'banido'], true)) {
            abort(404);
        }

        $contaSuspensa = $user->status_conta === 'banido';

        if ($contaSuspensa) {
            return view('usuarios.perfil', [
                'user' => $user,
                'contaSuspensa' => true,
            ]);
        }

        $idUsuario1 = min($usuarioAtual->id_usuario, $user->id_usuario);
        $idUsuario2 = max($usuarioAtual->id_usuario, $user->id_usuario);

        $amizade = Amizade::where('id_usuario_1', $idUsuario1)
            ->where('id_usuario_2', $idUsuario2)
            ->first();

        $estadoAmizade = 'nenhuma';

        if ($amizade?->status_amizade === 'aceita') {
            $estadoAmizade = 'amigos';
        } elseif ($amizade?->status_amizade === 'pendente') {
            $estadoAmizade = $amizade->id_solicitante === $usuarioAtual->id_usuario
                ? 'enviada'
                : 'recebida';
        }

        $user->load([
            'generos' => function ($query) {
                $query->orderBy('genero');
            },
            'jogos' => function ($query) {
                $query
                    ->orderByRaw('CASE WHEN tb_jogo_usuario.ordem_perfil IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('tb_jogo_usuario.ordem_perfil')
                    ->orderBy('tb_jogo.nome');
            },
            'plataformas' => function ($query) {
                $query
                    ->orderByRaw('tb_usuario_plataforma.ordem_perfil IS NULL')
                    ->orderBy('tb_usuario_plataforma.ordem_perfil')
                    ->orderBy('tb_usuario_plataforma.data_adicao')
                    ->orderBy('tb_plataforma.nome');
            },
        ]);

        $posts = $user->posts()
            ->where('status_post', 'ativo')
            ->withCount('reacoes')
            ->orderByRaw('fixado_em IS NULL')
            ->orderByDesc('fixado_em')
            ->orderByDesc('data_publicacao')
            ->orderByDesc('id_post')
            ->limit(4)
            ->get();

        $totalPosts = $user->posts()
            ->where('status_post', 'ativo')
            ->count();

        $podeReagir = $estadoAmizade === 'amigos';
        $postsReagidos = [];

        if ($podeReagir && $posts->isNotEmpty()) {
            $postsReagidos = DB::table('tb_post_reacao')
                ->where('id_usuario', $usuarioAtual->id_usuario)
                ->whereIn('id_post', $posts->pluck('id_post'))
                ->pluck('id_post')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return view('usuarios.perfil', [
            'user' => $user,
            'niveis' => NivelProficiencia::todos(),
            'presenca' => $user->presenca(),
            'contaSuspensa' => false,
            'amizade' => $amizade,
            'estadoAmizade' => $estadoAmizade,
            'posts' => $posts,
            'totalPosts' => $totalPosts,
            'podeReagir' => $podeReagir,
            'postsReagidos' => $postsReagidos,
        ]);
    }
}
