<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SteamService
{
    private const GENEROS_ACEITOS = [
        'Ação',
        'Aventura',
        'Casual',
        'Experimental',
        'Quebra-cabeça',
        'Corrida',
        'RPG',
        'Simulação',
        'Esportes',
        'Estratégia',
        'Jogos de Mesa',
        'RPG de Ação',
        'Ação/Aventura',
        'Arcade',
        'Batalha Automática',
        'Simulador Automobilístico',
        'Construção de Bases',
        'Beisebol',
        'Basquete',
        'Battle Royale',
        'BMX',
        'Jogo de Tabuleiro',
        'Boliche',
        'Construção',
        'Jogo de Cartas',
        'Ação em Terceira Pessoa',
        'Xadrez',
        'Cliques',
        'Ciclismo',
        'Diplomacia',
        'eSports',
        'Exploração',
        'Simulador Rural',
        'Luta',
        'Futebol Americano',
        'Jogo de Divindade',
        'Golfe',
        'Hackear',
        'Objeto Escondido',
        'Hockey',
        'Inatividade',
        'Ficção Interativa',
        'Gerenciamento',
        '3ª Partida',
        'Simulador Médico',
        'Minigolfe',
        'Mineração',
        'MMORPG',
        'MOBA',
        'Motocross',
        'Mundo Aberto',
        'Simulador de Epidemias',
        'RPG de Grupos',
        'Pinball',
        'Plataforma',
        'Apontar e Clicar',
        'Ritmo',
        'Roguelike',
        'Estratégia em Tempo Real (RTS)',
        'Faça o que Quiser',
        'Tiro',
        'Skate',
        'Patinação',
        'Esqui',
        'Snowboard',
        'Futebol',
        'Simulador Espacial',
        'Furtivo',
        'RPG de Estratégia',
        'Sobrevivência',
        'Tênis',
        'Defesa de Torres',
        'Perguntas e Respostas',
        'Estratégia Baseada em Turnos',
        'Romance Visual',
        'Simulador de Caminhadas',
        'Jogo de Palavras',
        'Luta Livre',
    ];

    private const MODOS_JOGADOR = [
        'Local para 4 Jogadores',
        'Multijogador Assíncrono',
        'Cooperativo',
        'Campanha Cooperativa',
        'Cooperativo Local',
        'Multijogador Local',
        'Multijogador Massivo',
        'Multijogador',
        'Cooperativo On-line',
        'Um Jogador',
    ];

    private const ALIASES_GENEROS = [
        'Ação e Aventura' => 'Ação/Aventura',
        'Quebra-Cabeças' => 'Quebra-cabeça',
        'Corridas' => 'Corrida',
        'Jogo de Mesa' => 'Jogos de Mesa',
    ];

    private const ALIASES_MODOS = [
        'Single-player' => 'Um Jogador',
        'Singleplayer' => 'Um Jogador',
        'Multi-player' => 'Multijogador',
        'Multiplayer' => 'Multijogador',
        'Co-op' => 'Cooperativo',
        'Cooperative' => 'Cooperativo',
        'Online Co-op' => 'Cooperativo On-line',
        'Local Co-op' => 'Cooperativo Local',
        'Shared/Split Screen Co-op' => 'Cooperativo Local',
        'Shared/Split Screen' => 'Multijogador Local',
        'Local Multiplayer' => 'Multijogador Local',
        'Massively Multiplayer' => 'Multijogador Massivo',
    ];

    public function buscarDetalhesJogo(int $appId): ?array
    {
        return Cache::remember(
            'steam_details_' . $appId,
            3600,
            function () use ($appId) {
                try {
                    $response = Http::connectTimeout(3)
                        ->timeout(6)
                        ->get(
                        'https://store.steampowered.com/api/appdetails',
                        [
                            'appids' => $appId,
                            'l' => 'brazilian',
                            'cc' => 'br',
                        ]
                    );
                } catch (Throwable) {
                    return null;
                }

                if (!$response->successful()) {
                    return null;
                }

                $resultado = $response->json();

                if (!($resultado[$appId]['success'] ?? false)) {
                    return null;
                }

                return $resultado[$appId]['data'] ?? null;
            }
        );
    }

    public function buscarClassificacoesJogo(
        int   $appId,
        array $detalhes
    ): array
    {
        $marcadores = $this->buscarMarcadoresJogo($appId);

        $generosSteam = collect(
            $detalhes['genres'] ?? []
        )
            ->pluck('description')
            ->filter()
            ->values()
            ->all();

        $categoriasSteam = collect(
            $detalhes['categories'] ?? []
        )
            ->pluck('description')
            ->filter()
            ->values()
            ->all();

        return [
            'generos' => $this->filtrarMarcadores(
                array_merge(
                    $generosSteam,
                    $marcadores
                ),
                self::GENEROS_ACEITOS,
                self::ALIASES_GENEROS
            ),

            'modos' => $this->filtrarMarcadores(
                array_merge(
                    $categoriasSteam,
                    $marcadores
                ),
                self::MODOS_JOGADOR,
                self::ALIASES_MODOS
            ),
        ];
    }

    public function buscarJogadoresOnline(int $appId): ?int
    {
        $resultados = $this->buscarJogadoresOnlineEmLote([$appId]);

        return $resultados[$appId] ?? null;
    }

    public function buscarJogadoresOnlineEmLote(array $appIds): array
    {
        $appIds = collect($appIds)
            ->filter()
            ->map(fn($appId) => (int)$appId)
            ->unique()
            ->values();

        $resultados = [];
        $faltantes = [];

        foreach ($appIds as $appId) {
            $cacheKey = 'steam_players_' . $appId;

            $cache = Cache::get($cacheKey);

            if (
                is_array($cache)
                && array_key_exists('value', $cache)
            ) {
                $resultados[$appId] = $cache['value'];
            } else {
                $faltantes[] = $appId;
            }
        }

        if (!empty($faltantes)) {
            $responses = Http::pool(
                function (Pool $pool) use ($faltantes) {
                    $requests = [];

                    foreach ($faltantes as $appId) {
                        $requests[] = $pool
                            ->as((string)$appId)
                            ->timeout(5)
                            ->get(
                                'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/',
                                [
                                    'appid' => $appId,
                                ]
                            );
                    }

                    return $requests;
                }
            );

            foreach ($faltantes as $appId) {
                $response = $responses[(string)$appId] ?? null;
                $quantidade = null;

                if (
                    $response
                    && !($response instanceof Throwable)
                    && $response->successful()
                ) {
                    $quantidade = $response->json(
                        'response.player_count'
                    );

                    if (is_numeric($quantidade)) {
                        $quantidade = (int)$quantidade;
                    } else {
                        $quantidade = null;
                    }
                }

                Cache::put(
                    'steam_players_' . $appId,
                    ['value' => $quantidade],
                    now()->addMinutes(2)
                );

                $resultados[$appId] = $quantidade;
            }
        }

        return $resultados;
    }

    private function buscarMarcadoresJogo(int $appId): array
    {
        $cacheKey = 'steam_tags_' . $appId;
        $cache = Cache::get($cacheKey);

        if (is_array($cache)) {
            return $cache;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' =>
                    'Mozilla/5.0 SquadMaker/1.0',
            ])
                ->withCookies([
                    'birthtime' => '568022401',
                    'lastagecheckage' => '1-January-1990',
                    'wants_mature_content' => '1',
                ], 'store.steampowered.com')
                ->connectTimeout(2)
                ->timeout(4)
                ->get(
                    'https://store.steampowered.com/app/' . $appId . '/',
                    [
                        'l' => 'brazilian',
                        'cc' => 'br',
                    ]
                );
        } catch (Throwable) {
            return [];
        }

        if (!$response->successful()) {
            return [];
        }

        preg_match_all(
            '/<a[^>]*class="[^"]*app_tag[^"]*"[^>]*>(.*?)<\/a>/si',
            $response->body(),
            $matches
        );

        $marcadores = collect($matches[1] ?? [])
            ->map(function ($marcador) {
                return trim(
                    html_entity_decode(
                        strip_tags($marcador),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    )
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        Cache::put(
            $cacheKey,
            $marcadores,
            now()->addHours(6)
        );

        return $marcadores;
    }

    private function filtrarMarcadores(
        array $valores,
        array $permitidos,
        array $aliases = []
    ): array
    {
        $mapa = [];

        foreach ($permitidos as $permitido) {
            $mapa[$this->normalizarMarcador($permitido)] = $permitido;
        }

        foreach ($aliases as $alias => $valorFinal) {
            $mapa[$this->normalizarMarcador($alias)] = $valorFinal;
        }

        $resultado = [];

        foreach ($valores as $valor) {
            if (!is_string($valor)) {
                continue;
            }

            $normalizado = $this->normalizarMarcador($valor);

            if (!isset($mapa[$normalizado])) {
                continue;
            }

            $resultado[] = $mapa[$normalizado];
        }

        return array_values(
            array_unique($resultado)
        );
    }

    private function normalizarMarcador(string $valor): string
    {
        $valor = Str::ascii(
            mb_strtolower(
                trim($valor)
            )
        );

        $valor = preg_replace(
            '/[^a-z0-9]+/',
            ' ',
            $valor
        );

        $valor = preg_replace(
            '/\s+/',
            ' ',
            trim($valor)
        );

        $valor = str_replace(
            [
                'on line',
                'co op',
            ],
            [
                'online',
                'coop',
            ],
            $valor
        );

        return $valor;
    }
}
