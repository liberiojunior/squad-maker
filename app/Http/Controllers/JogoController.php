<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Jogo;
use App\Models\ModoJogo;
use App\Services\CompatibilidadeJogoService;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class JogoController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'ordem' => ['nullable', 'in:popularidade,az,za'],
            'generos' => ['nullable', 'array'],
            'generos.*' => ['integer', 'distinct', 'exists:tb_genero,id_genero'],
            'modos' => ['nullable', 'array'],
            'modos.*' => ['integer', 'distinct', 'exists:tb_modo_jogo,id_modo_jogo'],
        ]);

        $query = Jogo::query();
        $jogosUsuario = $request->user()->jogos()->get()->keyBy('id_jogo');

        $busca = trim($data['q'] ?? '');
        $ordem = $data['ordem'] ?? 'popularidade';
        $generosSelecionados = $data['generos'] ?? [];
        $modosSelecionados = $data['modos'] ?? [];

        if ($busca !== '') {
            $query->where('nome', 'like', '%' . $busca . '%');
        }

        foreach ($generosSelecionados as $idGenero) {
            $query->whereHas('generos', function ($generosQuery) use ($idGenero) {
                $generosQuery->where('tb_genero.id_genero', $idGenero);
            });
        }

        foreach ($modosSelecionados as $idModo) {
            $query->whereHas('modos', function ($modosQuery) use ($idModo) {
                $modosQuery->where('tb_modo_jogo.id_modo_jogo', $idModo);
            });
        }

        if ($ordem === 'az') {
            $query->orderBy('nome');
        } elseif ($ordem === 'za') {
            $query->orderBy('nome', 'desc');
        } else {
            $query->orderByDesc('jogadores_online')->orderBy('nome');
        }

        $jogos = $query->paginate(12)->withQueryString();

        $this->solicitarAtualizacaoPopularidade();

        return $this->viewBusca(
            $jogos,
            $generosSelecionados,
            $modosSelecionados,
            $jogosUsuario
        );
    }

    public function show(
        Request $request,
        Jogo $jogo,
        CompatibilidadeJogoService $compatibilidade
    ) {
        $data = $request->validate([
            'nivel' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $nivelFiltro = isset($data['nivel'])
            ? (int) $data['nivel']
            : null;

        $jogo->load([
            'generos' => function ($query) {
                $query->orderBy('genero');
            },
            'modos' => function ($query) {
                $query->orderBy('nome');
            },
        ]);

        $resultado = $compatibilidade->buscarJogadores(
            $jogo,
            $request->user(),
            $nivelFiltro
        );

        return view('jogos.show', [
            'jogo' => $jogo,
            'jogadores' => $resultado['jogadores'],
            'nivelUsuario' => $resultado['nivel_usuario'],
            'nivelFiltro' => $nivelFiltro,
            'niveis' => NivelProficiencia::detalhes(),
        ]);
    }

    private function viewBusca(
        LengthAwarePaginator $jogos,
        array $generosSelecionados,
        array $modosSelecionados,
        $jogosUsuario
    ) {
        $generos = Genero::query()
            ->whereHas('jogos')
            ->orderBy('genero')
            ->get();

        $modos = ModoJogo::query()
            ->whereHas('jogos')
            ->orderBy('nome')
            ->get();

        return view('jogos.buscar', [
            'jogos' => $jogos,
            'generos' => $generos,
            'modos' => $modos,
            'generosSelecionados' => array_map('intval', $generosSelecionados),
            'modosSelecionados' => array_map('intval', $modosSelecionados),
            'jogosUsuario' => $jogosUsuario,
            'descricoesNiveis' => NivelProficiencia::descricoesSelecao(),
        ]);
    }

    private function solicitarAtualizacaoPopularidade(): void
    {
        $cacheKey = 'steam_popularidade_fila';

        if (!Cache::add($cacheKey, true, now()->addMinutes(2))) {
            return;
        }

        try {
            Artisan::queue('steam:atualizar-popularidade', [
                '--limit' => 200,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            report($exception);
        }
    }
}
