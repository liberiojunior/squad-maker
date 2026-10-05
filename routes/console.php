<?php

use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('steam:atualizar-popularidade {--limit=200}', function () {
    $limite = max(1, min((int) $this->option('limit'), 500));
    $limiteAtualizacao = now()->subMinutes(2);

    $jogos = Jogo::query()
        ->whereNotNull('steam_app_id')
        ->where(function ($query) use ($limiteAtualizacao) {
            $query->whereNull('jogadores_online_atualizado_em')
                ->orWhere('jogadores_online_atualizado_em', '<=', $limiteAtualizacao);
        })
        ->orderBy('jogadores_online_atualizado_em')
        ->orderBy('id_jogo')
        ->limit($limite)
        ->get([
            'id_jogo',
            'steam_app_id',
        ]);

    if ($jogos->isEmpty()) {
        $this->info('Nenhum jogo precisa ser atualizado agora.');

        return;
    }

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

    $this->info(
        count($atualizacoes)
        .' jogo(s) atualizado(s). '
        .$falhas
        .' falha(s) mantiveram o último valor conhecido.'
    );
})->purpose('Atualiza a popularidade dos jogos usando jogadores online da Steam.');
