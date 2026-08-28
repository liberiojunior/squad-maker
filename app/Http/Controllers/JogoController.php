<?php

namespace App\Http\Controllers;

use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class JogoController extends Controller
{
    public function index(Request $request, SteamService $steam)
    {
        $query = Jogo::query();

        if ($request->filled('q')) {
            $query->where(
                'nome',
                'like',
                '%' . trim($request->q) . '%'
            );
        }

        $ordem = $request->input('ordem', 'popularidade');

        if ($ordem === 'az') {
            $jogos = $query
                ->orderBy('nome')
                ->paginate(12)
                ->withQueryString();

            $this->adicionarJogadoresOnline($jogos, $steam);

            return view('jogos.buscar', [
                'jogos' => $jogos,
            ]);
        }

        if ($ordem === 'za') {
            $jogos = $query
                ->orderBy('nome', 'desc')
                ->paginate(12)
                ->withQueryString();

            $this->adicionarJogadoresOnline($jogos, $steam);

            return view('jogos.buscar', [
                'jogos' => $jogos,
            ]);
        }

        $todosJogos = $query->get();

        $appIds = $todosJogos
            ->pluck('steam_app_id')
            ->filter()
            ->all();

        $jogadoresOnline = $steam
            ->buscarJogadoresOnlineEmLote($appIds);

        foreach ($todosJogos as $jogo) {
            $jogo->jogadores_online = $jogo->steam_app_id
                ? ($jogadoresOnline[$jogo->steam_app_id] ?? null)
                : null;
        }

        $todosJogos = $todosJogos
            ->sortByDesc(function ($jogo) {
                return $jogo->jogadores_online ?? -1;
            })
            ->values();

        $pagina = LengthAwarePaginator::resolveCurrentPage();

        $jogos = new LengthAwarePaginator(
            $todosJogos->forPage($pagina, 12)->values(),
            $todosJogos->count(),
            12,
            $pagina,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('jogos.buscar', [
            'jogos' => $jogos,
        ]);
    }

    private function adicionarJogadoresOnline(
        LengthAwarePaginator $jogos,
        SteamService $steam
    ): void {
        $appIds = $jogos
            ->getCollection()
            ->pluck('steam_app_id')
            ->filter()
            ->all();

        $jogadoresOnline = $steam
            ->buscarJogadoresOnlineEmLote($appIds);

        $jogos->getCollection()->transform(
            function ($jogo) use ($jogadoresOnline) {

                $jogo->jogadores_online = $jogo->steam_app_id
                    ? ($jogadoresOnline[$jogo->steam_app_id] ?? null)
                    : null;

                return $jogo;
            }
        );
    }
}
