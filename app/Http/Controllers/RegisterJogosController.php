<?php

namespace App\Http\Controllers;

use App\Models\Jogo;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterJogosController extends Controller
{
    private const LIMITE_JOGOS = 3;

    public function show(Request $request)
    {
        $query = Jogo::orderBy('nome');

        if ($request->filled('q')) {
            $query->where(
                'nome',
                'like',
                '%' . $request->q . '%'
            );
        }

        $jogos = $query
            ->paginate(12)
            ->withQueryString();

        $selecionados = $request->session()->get(
            $this->sessionKey($request->user()->id_usuario),
            []
        );

        return view('register.cadastro-jogos', [
            'jogos' => $jogos,
            'niveis' => NivelProficiencia::todos(),
            'selecionados' => $selecionados,
            'limiteJogos' => self::LIMITE_JOGOS,
        ]);
    }

    public function selectGame(Request $request)
    {
        $data = $request->validate([
            'id_jogo' => [
                'required',
                'integer',
                'exists:tb_jogo,id_jogo',
            ],
            'nivel' => [
                'required',
                'integer',
                'between:1,5',
            ],
        ]);

        $key = $this->sessionKey(
            $request->user()->id_usuario
        );

        $selecionados = $request->session()->get(
            $key,
            []
        );

        $idJogo = (int)$data['id_jogo'];

        if (
            !array_key_exists($idJogo, $selecionados)
            && count($selecionados) >= self::LIMITE_JOGOS
        ) {
            return response()->json([
                'message' => 'Você pode escolher no máximo 3 jogos no cadastro inicial.',
            ], 422);
        }

        $selecionados[$idJogo] = (int)$data['nivel'];

        $request->session()->put(
            $key,
            $selecionados
        );

        return response()->json([
            'success' => true,
            'count' => count($selecionados),
            'nivel' => (int)$data['nivel'],
            'nivel_nome' => NivelProficiencia::nome(
                (int)$data['nivel']
            ),
        ]);
    }

    public function removeGame(
        Request $request,
        Jogo    $jogo
    )
    {
        $key = $this->sessionKey(
            $request->user()->id_usuario
        );

        $selecionados = $request->session()->get(
            $key,
            []
        );

        unset(
            $selecionados[$jogo->id_jogo]
        );

        $request->session()->put(
            $key,
            $selecionados
        );

        return response()->json([
            'success' => true,
            'count' => count($selecionados),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $key = $this->sessionKey(
            $user->id_usuario
        );

        $selecionados = $request->session()->get(
            $key,
            []
        );

        if (empty($selecionados)) {
            return back()->withErrors([
                'jogos' => 'Escolha pelo menos um jogo para continuar.',
            ]);
        }

        if (count($selecionados) > self::LIMITE_JOGOS) {
            return back()->withErrors([
                'jogos' => 'Escolha no máximo 3 jogos.',
            ]);
        }

        $ids = array_map(
            'intval',
            array_keys($selecionados)
        );

        $jogosExistentes = Jogo::whereIn(
            'id_jogo',
            $ids
        )
            ->pluck('id_jogo')
            ->map(fn($id) => (int)$id)
            ->toArray();

        if (count($jogosExistentes) !== count($ids)) {
            return back()->withErrors([
                'jogos' => 'Um dos jogos selecionados não está mais disponível.',
            ]);
        }

        $jogosSalvar = [];

        foreach ($selecionados as $idJogo => $nivel) {
            $jogosSalvar[(int)$idJogo] = [
                'data_adicao' => now(),
                'nivel_proficiencia' => (int)$nivel,
            ];
        }

        DB::transaction(function () use (
            $user,
            $jogosSalvar
        ) {
            $user->jogos()->sync(
                $jogosSalvar
            );
        });

        $request->session()->forget(
            $key
        );

        return redirect()
            ->route('perfil')
            ->with(
                'success',
                'Jogos adicionados ao seu perfil.'
            );
    }

    private function sessionKey(int $idUsuario): string
    {
        return 'cadastro_jogos_' . $idUsuario;
    }
}
