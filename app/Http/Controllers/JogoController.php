<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Jogo;
use App\Models\ModoJogo;
use App\Services\CompatibilidadeJogoService;
use App\Services\SteamService;
use App\Support\NivelProficiencia;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class JogoController extends Controller
{
    public function index(
        Request      $request,
        SteamService $steam
    )
    {
        $data = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:150',
            ],

            'ordem' => [
                'nullable',
                'in:popularidade,az,za',
            ],

            'generos' => [
                'nullable',
                'array',
            ],

            'generos.*' => [
                'integer',
                'distinct',
                'exists:tb_genero,id_genero',
            ],

            'modos' => [
                'nullable',
                'array',
            ],

            'modos.*' => [
                'integer',
                'distinct',
                'exists:tb_modo_jogo,id_modo_jogo',
            ],
        ]);


        $query = Jogo::query();

        $busca = trim(
            $data['q'] ?? ''
        );

        $ordem =
            $data['ordem']
            ?? 'popularidade';

        $generosSelecionados =
            $data['generos']
            ?? [];

        $modosSelecionados =
            $data['modos']
            ?? [];

        if ($busca !== '') {
            $query->where(
                'nome',
                'like',
                '%' . $busca . '%'
            );
        }

        foreach (
            $generosSelecionados
            as $idGenero
        ) {
            $query->whereHas(
                'generos',
                function ($generosQuery) use (
                    $idGenero
                ) {
                    $generosQuery->where(
                        'tb_genero.id_genero',
                        $idGenero
                    );
                }
            );
        }

        foreach (
            $modosSelecionados
            as $idModo
        ) {
            $query->whereHas(
                'modos',
                function ($modosQuery) use (
                    $idModo
                ) {
                    $modosQuery->where(
                        'tb_modo_jogo.id_modo_jogo',
                        $idModo
                    );
                }
            );
        }

        if ($ordem === 'az') {

            $jogos = $query
                ->orderBy('nome')
                ->paginate(12)
                ->withQueryString();

            $this->adicionarJogadoresOnline(
                $jogos,
                $steam
            );

            return $this->viewBusca(
                $jogos,
                $generosSelecionados,
                $modosSelecionados
            );
        }


        if ($ordem === 'za') {

            $jogos = $query
                ->orderBy(
                    'nome',
                    'desc'
                )
                ->paginate(12)
                ->withQueryString();

            $this->adicionarJogadoresOnline(
                $jogos,
                $steam
            );

            return $this->viewBusca(
                $jogos,
                $generosSelecionados,
                $modosSelecionados
            );
        }

        $todosJogos = $query->get();

        $appIds = $todosJogos
            ->pluck('steam_app_id')
            ->filter()
            ->all();


        $jogadoresOnline = $steam
            ->buscarJogadoresOnlineEmLote(
                $appIds
            );


        foreach ($todosJogos as $jogo) {

            $jogo->jogadores_online =
                $jogo->steam_app_id
                    ? (
                    $jogadoresOnline[$jogo->steam_app_id] ?? null
                )
                    : null;
        }


        $todosJogos = $todosJogos
            ->sortByDesc(
                function ($jogo) {
                    return
                        $jogo->jogadores_online
                        ?? -1;
                }
            )
            ->values();


        $pagina =
            LengthAwarePaginator::
            resolveCurrentPage();


        $jogos = new LengthAwarePaginator(
            $todosJogos
                ->forPage(
                    $pagina,
                    12
                )
                ->values(),

            $todosJogos->count(),

            12,

            $pagina,

            [
                'path' =>
                    $request->url(),

                'query' =>
                    $request->query(),
            ]
        );


        return $this->viewBusca(
            $jogos,
            $generosSelecionados,
            $modosSelecionados
        );
    }


    public function show(
        Request                    $request,
        Jogo                       $jogo,
        CompatibilidadeJogoService $compatibilidade
    )
    {
        $data = $request->validate([
            'nivel' => [
                'nullable',
                'integer',
                'between:1,5',
            ],
        ]);


        $nivelFiltro =
            isset($data['nivel'])
                ? (int)$data['nivel']
                : null;


        $jogo->load([
            'generos' => function ($query) {
                $query->orderBy('genero');
            },

            'modos' => function ($query) {
                $query->orderBy('nome');
            },
        ]);


        $resultado =
            $compatibilidade
                ->buscarJogadores(
                    $jogo,
                    $request->user(),
                    $nivelFiltro
                );


        return view(
            'jogos.show',
            [
                'jogo' =>
                    $jogo,

                'jogadores' =>
                    $resultado['jogadores'],

                'nivelUsuario' =>
                    $resultado['nivel_usuario'],

                'nivelFiltro' =>
                    $nivelFiltro,

                'niveis' =>
                    NivelProficiencia::
                    detalhes(),
            ]
        );
    }


    private function viewBusca(
        LengthAwarePaginator $jogos,
        array                $generosSelecionados,
        array                $modosSelecionados
    )
    {
        $generos = Genero::query()
            ->whereHas('jogos')
            ->orderBy('genero')
            ->get();


        $modos = ModoJogo::query()
            ->whereHas('jogos')
            ->orderBy('nome')
            ->get();


        return view(
            'jogos.buscar',
            [
                'jogos' =>
                    $jogos,

                'generos' =>
                    $generos,

                'modos' =>
                    $modos,

                'generosSelecionados' =>
                    array_map(
                        'intval',
                        $generosSelecionados
                    ),

                'modosSelecionados' =>
                    array_map(
                        'intval',
                        $modosSelecionados
                    ),
            ]
        );
    }


    private function adicionarJogadoresOnline(
        LengthAwarePaginator $jogos,
        SteamService         $steam
    ): void
    {
        $appIds = $jogos
            ->getCollection()
            ->pluck('steam_app_id')
            ->filter()
            ->all();


        $jogadoresOnline = $steam
            ->buscarJogadoresOnlineEmLote(
                $appIds
            );


        $jogos
            ->getCollection()
            ->transform(
                function ($jogo) use (
                    $jogadoresOnline
                ) {
                    $jogo->jogadores_online =
                        $jogo->steam_app_id
                            ? (
                            $jogadoresOnline[$jogo->steam_app_id] ?? null
                        )
                            : null;

                    return $jogo;
                }
            );
    }
}
