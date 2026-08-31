<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Jogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plataforma;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $user->load([
            'generos',
            'jogos',
            'plataformas',
        ]);

        $generos = Genero::orderBy('genero')->get();
        $jogosDisponiveis = Jogo::orderBy('nome')->get();
        $plataformasDisponiveis = Plataforma::orderBy('nome')->get();

        return view('perfil', [
            'user' => $user,
            'generos' => $generos,
            'jogosDisponiveis' => $jogosDisponiveis,
            'plataformasDisponiveis' => $plataformasDisponiveis,
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

    public function updateJogos(Request $request)
    {
        $data = $request->validate([
            'jogos' => ['nullable', 'array'],
            'jogos.*' => [
                'integer',
                'exists:tb_jogo,id_jogo',
            ],
        ]);

        $user = $request->user();

        $jogosSelecionados = $data['jogos'] ?? [];

        $jogosAtuais = $user->jogos()
            ->pluck('tb_jogo.id_jogo')
            ->toArray();

        $jogosAdicionar = array_diff(
            $jogosSelecionados,
            $jogosAtuais
        );

        $jogosRemover = array_diff(
            $jogosAtuais,
            $jogosSelecionados
        );

        foreach ($jogosAdicionar as $idJogo) {
            $user->jogos()->attach($idJogo, [
                'data_adicao' => now(),
            ]);
        }

        if (! empty($jogosRemover)) {
            $user->jogos()->detach($jogosRemover);
        }

        return redirect()
            ->route('perfil')
            ->with('success', 'Jogos atualizados.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'confirmacao' => ['required', 'string'],
        ]);

        if ($request->confirmacao !== $user->nickname) {
            return back()->withErrors([
                'confirmacao' =>
                    'O nome digitado não corresponde ao seu usuário.',
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

        $selecionadas =
            $data['plataformas'] ?? [];

        $atuais = $user->plataformas()
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

            $user->plataformas()->attach(
                $idPlataforma,
                [
                    'data_adicao' => now(),
                ]
            );
        }

        if (! empty($remover)) {
            $user->plataformas()
                ->detach($remover);
        }

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Plataformas atualizadas.'
            );
    }
}
