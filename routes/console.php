<?php

use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('steam:atualizar-popularidade {--limit=100}', function () {
    $limite = max(1, min((int) $this->option('limit'), 100));
    $limiteAtualizacao = now()->subMinutes(45);
    $colunas = ['id_jogo', 'steam_app_id'];

    $populares = Jogo::query()
        ->whereNotNull('steam_app_id')
        ->whereNotNull('jogadores_online')
        ->where('jogadores_online_atualizado_em', '<=', $limiteAtualizacao)
        ->orderByDesc('jogadores_online')
        ->limit(min(10, $limite))
        ->get($colunas);

    $limiteRodizio = $limite - $populares->count();
    $rodizio = collect();

    if ($limiteRodizio > 0) {
        $query = Jogo::query()
            ->whereNotNull('steam_app_id')
            ->whereNotIn('id_jogo', $populares->pluck('id_jogo')->all())
            ->where(function ($query) use ($limiteAtualizacao) {
                $query->whereNull('jogadores_online_atualizado_em')
                    ->orWhere('jogadores_online_atualizado_em', '<=', $limiteAtualizacao);
            });

        $ultimoId = Cache::get('steam_popularidade_ultimo_id');

        if ($ultimoId === null) {
            $total = (clone $query)->count();
            $posicao = (intdiv(now()->getTimestamp(), 3600) * $limiteRodizio) % max(1, $total);

            $ultimoId = (clone $query)
                ->orderBy('id_jogo')
                ->skip($posicao)
                ->value('id_jogo') ?? 0;
        }

        $rodizio = (clone $query)
            ->where('id_jogo', '>', (int) $ultimoId)
            ->orderBy('id_jogo')
            ->limit($limiteRodizio)
            ->get($colunas);

        if ($rodizio->count() < $limiteRodizio) {
            $inicio = (clone $query)
                ->where('id_jogo', '<=', (int) $ultimoId)
                ->orderBy('id_jogo')
                ->limit($limiteRodizio - $rodizio->count())
                ->get($colunas);

            $rodizio = $rodizio->concat($inicio);
        }

        if ($rodizio->isNotEmpty()) {
            Cache::put('steam_popularidade_ultimo_id', $rodizio->last()->id_jogo, now()->addDays(7));
        }
    }

    $jogos = $populares->concat($rodizio);

    if ($jogos->isEmpty()) {
        $this->info('Nenhum jogo precisa ser atualizado agora.');
        return;
    }

    $resultados = app(SteamService::class)->buscarJogadoresOnlineEmLote(
        $jogos->pluck('steam_app_id')->all(),
        false
    );

    $atualizacoes = [];
    $falhas = 0;
    $agora = now();

    foreach ($jogos as $jogo) {
        $quantidade = $resultados[$jogo->steam_app_id] ?? null;

        if (!is_int($quantidade)) {
            $falhas++;
            continue;
        }

        $atualizacoes[] = [
            'id_jogo' => $jogo->id_jogo,
            'jogadores_online' => $quantidade,
            'jogadores_online_atualizado_em' => $agora,
        ];
    }

    if ($atualizacoes) {
        DB::table('tb_jogo')->upsert(
            $atualizacoes,
            ['id_jogo'],
            ['jogadores_online', 'jogadores_online_atualizado_em']
        );
    }

    Log::info('Steam: atualização de jogadores concluída', [
        'consultados' => $jogos->count(),
        'atualizados' => count($atualizacoes),
        'sem_resposta_valida' => $falhas,
    ]);

    $this->info(count($atualizacoes) . ' jogo(s) atualizado(s). ' . $falhas . ' falha(s) mantiveram o último valor conhecido.');
})->purpose('Atualiza a popularidade dos jogos usando jogadores online da Steam.');
