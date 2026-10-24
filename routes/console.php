<?php

use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('steam:atualizar-popularidade {--limit=200}', function () {
    $limite = max(1, min((int) $this->option('limit'), 500));
    $limiteAtualizacao = now()->subMinutes(2);

    $ultimoId = (int) Cache::get('steam_popularidade_ultimo_id', 0);
    $colunas = ['id_jogo', 'steam_app_id'];

    $query = Jogo::query()
        ->whereNotNull('steam_app_id')
        ->where(function ($query) use ($limiteAtualizacao) {
            $query->whereNull('jogadores_online_atualizado_em')
                ->orWhere('jogadores_online_atualizado_em', '<=', $limiteAtualizacao);
        });

    $jogos = (clone $query)
        ->where('id_jogo', '>', $ultimoId)
        ->orderBy('id_jogo')
        ->limit($limite)
        ->get($colunas);

    if ($jogos->count() < $limite) {
        $inicio = (clone $query)
            ->where('id_jogo', '<=', $ultimoId)
            ->orderBy('id_jogo')
            ->limit($limite - $jogos->count())
            ->get($colunas);

        $jogos = $jogos->concat($inicio);
    }

    if ($jogos->isEmpty()) {
        $this->info('Nenhum jogo precisa ser atualizado agora.');

        return;
    }

    Cache::put('steam_popularidade_ultimo_id', $jogos->last()->id_jogo, now()->addDays(7));

    $steam = app(SteamService::class);

    $resultados = $steam->buscarJogadoresOnlineEmLote(
        $jogos->pluck('steam_app_id')->all(),
        false
    );

    $agora = now();
    $atualizacoes = [];
    $falhas = 0;

    foreach ($jogos as $jogo) {
        $quantidade = $resultados[$jogo->steam_app_id] ?? null;

        if (! is_int($quantidade)) {
            $falhas++;

            continue;
        }

        $atualizacoes[] = [
            'id_jogo' => $jogo->id_jogo,
            'jogadores_online' => $quantidade,
            'jogadores_online_atualizado_em' => $agora,
        ];
    }

    DB::transaction(function () use ($atualizacoes) {
        foreach ($atualizacoes as $atualizacao) {
            DB::table('tb_jogo')
                ->where('id_jogo', $atualizacao['id_jogo'])
                ->update([
                    'jogadores_online' => $atualizacao['jogadores_online'],
                    'jogadores_online_atualizado_em' => $atualizacao['jogadores_online_atualizado_em'],
                ]);
        }
    });

    Log::info('Steam: atualização de jogadores concluída', [
        'consultados' => $jogos->count(),
        'atualizados' => count($atualizacoes),
        'sem_resposta_valida' => $falhas,
    ]);

    $this->info(
        count($atualizacoes)
        .' jogo(s) atualizado(s). '
        .$falhas
        .' falha(s) mantiveram o último valor conhecido.'
    );
})->purpose('Atualiza a popularidade dos jogos usando jogadores online da Steam.');
