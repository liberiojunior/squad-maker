<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Jogo;
use App\Models\Plataforma;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $user->load([
            'generos',
            'jogos' => function ($query) {
                $query
                    ->orderByRaw('tb_jogo_usuario.ordem_perfil IS NULL')
                    ->orderBy('tb_jogo_usuario.ordem_perfil')
                    ->orderBy('tb_jogo_usuario.data_adicao');
            },
            'plataformas',
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

        return view('perfil', [
            'user' => $user,
            'generos' => $generos,
            'plataformasDisponiveis' => $plataformasDisponiveis,
            'niveis' => NivelProficiencia::todos(),
            'niveisJogosUsuario' => $niveisJogosUsuario,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nickname' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string'],
        ]);

        $user = $request->user();

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
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');

        $fileName = 'avatar_'
            . $user->id_usuario
            . '_'
            . time()
            . '.'
            . $file->getClientOriginalExtension();

        $file->move(
            public_path('uploads/avatars'),
            $fileName
        );

        $user->avatar = '/uploads/avatars/' . $fileName;
        $user->save();

        return redirect()
            ->route('perfil')
            ->with('success', 'Foto atualizada.');
    }

    public function updateGeneros(Request $request)
    {
        $data = $request->validate([
            'generos' => ['nullable', 'array'],
            'generos.*' => [
                'integer',
                'exists:tb_genero,id_genero',
            ],
        ]);

        $request->user()
            ->generos()
            ->sync($data['generos'] ?? []);

        return redirect()
            ->route('perfil')
            ->with('success', 'Gêneros atualizados.');
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
            'plataformas' => [
                'nullable',
                'array',
            ],
            'plataformas.*' => [
                'integer',
                'exists:tb_plataforma,id_plataforma',
            ],
        ]);

        $user = $request->user();
        $selecionadas = $data['plataformas'] ?? [];

        $atuais = $user
            ->plataformas()
            ->pluck('tb_plataforma.id_plataforma')
            ->toArray();

        $adicionar = array_diff(
            $selecionadas,
            $atuais
        );

        $remover = array_diff(
            $atuais,
            $selecionadas
        );

        foreach ($adicionar as $idPlataforma) {
            $user
                ->plataformas()
                ->attach(
                    $idPlataforma,
                    [
                        'data_adicao' => now(),
                    ]
                );
        }

        if (!empty($remover)) {
            $user
                ->plataformas()
                ->detach($remover);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Plataformas atualizadas.'
            );
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
}
