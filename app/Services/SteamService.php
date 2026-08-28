<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Throwable;

class SteamService
{
    public function buscarDetalhesJogo(int $appId): ?array
    {
        return Cache::remember(
            'steam_details_' . $appId,
            3600,
            function () use ($appId) {

                $response = Http::timeout(10)->get(
                    'https://store.steampowered.com/api/appdetails',
                    [
                        'appids' => $appId,
                        'l' => 'brazilian',
                        'cc' => 'br',
                    ]
                );

                if (! $response->successful()) {
                    return null;
                }

                $resultado = $response->json();

                if (! ($resultado[$appId]['success'] ?? false)) {
                    return null;
                }

                return $resultado[$appId]['data'] ?? null;
            }
        );
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
            ->map(fn ($appId) => (int) $appId)
            ->unique()
            ->values();

        $resultados = [];
        $faltantes = [];

        foreach ($appIds as $appId) {
            $cacheKey = 'steam_players_' . $appId;

            $cache = Cache::get($cacheKey);

            if (is_array($cache) && array_key_exists('value', $cache)) {
                $resultados[$appId] = $cache['value'];
            } else {
                $faltantes[] = $appId;
            }
        }

        if (! empty($faltantes)) {
            $responses = Http::pool(function (Pool $pool) use ($faltantes) {
                $requests = [];

                foreach ($faltantes as $appId) {
                    $requests[] = $pool
                        ->as((string) $appId)
                        ->timeout(5)
                        ->get(
                            'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/',
                            [
                                'appid' => $appId,
                            ]
                        );
                }

                return $requests;
            });

            foreach ($faltantes as $appId) {
                $response = $responses[(string) $appId] ?? null;

                $quantidade = null;

                if (
                    $response
                    && ! ($response instanceof Throwable)
                    && $response->successful()
                ) {
                    $quantidade = $response->json('response.player_count');

                    if (is_numeric($quantidade)) {
                        $quantidade = (int) $quantidade;
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
}
