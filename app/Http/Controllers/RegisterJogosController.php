<?php

namespace App\Http\Controllers;

use App\Models\Jogo;
use App\Models\User;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterJogosController extends Controller
{
    private const LIMITE_JOGOS = 3;

    public function show(Request $request)
    {
        if ($request->user()->cadastro_jogos_concluido_em) {
            return redirect()->route('perfil');
        }

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $busca = trim($data['q'] ?? '');
        $query = Jogo::query()->orderBy('nome');

        if ($busca !== '') {
            $query->where('nome', 'like', '%' . $busca . '%');
        }

        return view('register.cadastro-jogos', [
            'jogos' => $query->paginate(12)->withQueryString(),
            'niveis' => NivelProficiencia::todos(),
            'descricoesNiveis' => NivelProficiencia::descricoesSelecao(),
            'selecionados' => $request->session()->get($this->sessionKey($request->user()->id_usuario), []),
            'limiteJogos' => self::LIMITE_JOGOS,
        ]);
    }

    public function selectGame(Request $request)
    {
        if ($request->user()->cadastro_jogos_concluido_em) {
            return response()->json(['message' => 'O cadastro inicial de jogos já foi concluído.'], 409);
        }

        $data = $request->validate([
            'id_jogo' => ['required', 'integer', 'exists:tb_jogo,id_jogo'],
            'nivel' => ['required', 'integer', 'between:1,5'],
        ]);

        $key = $this->sessionKey($request->user()->id_usuario);
        $selecionados = $request->session()->get($key, []);
        $idJogo = (int) $data['id_jogo'];

        if (! array_key_exists($idJogo, $selecionados) && count($selecionados) >= self::LIMITE_JOGOS) {
            return response()->json([
                'message' => 'Você pode escolher no máximo 3 jogos no cadastro inicial.',
            ], 422);
        }

        $selecionados[$idJogo] = (int) $data['nivel'];
        $request->session()->put($key, $selecionados);

        return response()->json([
            'success' => true,
            'count' => count($selecionados),
            'nivel' => (int) $data['nivel'],
            'nivel_nome' => NivelProficiencia::nome((int) $data['nivel']),
        ]);
    }

    public function removeGame(Request $request, Jogo $jogo)
    {
        if ($request->user()->cadastro_jogos_concluido_em) {
            return response()->json(['message' => 'O cadastro inicial de jogos já foi concluído.'], 409);
        }

        $key = $this->sessionKey($request->user()->id_usuario);
        $selecionados = $request->session()->get($key, []);
        unset($selecionados[$jogo->id_jogo]);
        $request->session()->put($key, $selecionados);

        return response()->json([
            'success' => true,
            'count' => count($selecionados),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $key = $this->sessionKey($user->id_usuario);

        if ($user->cadastro_jogos_concluido_em) {
            $request->session()->forget($key);

            return redirect()->route('perfil');
        }

        $selecionados = $request->session()->get($key, []);

        if (empty($selecionados)) {
            return back()->withErrors(['jogos' => 'Escolha pelo menos um jogo para continuar.']);
        }

        if (count($selecionados) > self::LIMITE_JOGOS) {
            return back()->withErrors(['jogos' => 'Escolha no máximo 3 jogos.']);
        }

        $ids = array_map('intval', array_keys($selecionados));

        if (Jogo::whereIn('id_jogo', $ids)->count() !== count($ids)) {
            return back()->withErrors(['jogos' => 'Um dos jogos selecionados não está mais disponível.']);
        }

        foreach ($selecionados as $nivel) {
            if (! in_array((int) $nivel, [1, 2, 3, 4, 5], true)) {
                return back()->withErrors(['jogos' => 'O nível selecionado é inválido.']);
            }
        }

        $jogosSalvar = [];
        $ordem = 1;

        foreach ($selecionados as $idJogo => $nivel) {
            $jogosSalvar[(int) $idJogo] = [
                'data_adicao' => now(),
                'nivel_proficiencia' => (int) $nivel,
                'ordem_perfil' => $ordem++,
            ];
        }

        DB::transaction(function () use ($user, $jogosSalvar) {
            $usuario = User::query()->whereKey($user->id_usuario)->lockForUpdate()->firstOrFail();

            if ($usuario->cadastro_jogos_concluido_em) {
                return;
            }

            $usuario->jogos()->sync($jogosSalvar);
            $usuario->cadastro_jogos_concluido_em = now();
            $usuario->save();
        });

        $request->session()->forget($key);

        return redirect()->route('perfil')->with('success', 'Jogos adicionados ao seu perfil.');
    }

    private function sessionKey(int $idUsuario): string
    {
        return 'cadastro_jogos_' . $idUsuario;
    }
}
