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

Artisan::command('steam:atualizar-popularidade {--limit=100} {--force} {--all}', function () {
    $forcar = (bool) $this->option('force');
    $todos = (bool) $this->option('all');
    $limiteInformado = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($limiteInformado === false) {
        $this->error('Informe um limite inteiro maior que zero.');
        return 1;
    }

    if ($todos && !$forcar) {
        $this->error('A opção --all deve ser utilizada com --force.');
        return 1;
    }

    $limite = $forcar ? $limiteInformado : min($limiteInformado, 100);
    $colunas = ['id_jogo', 'steam_app_id'];
    $steam = app(SteamService::class);
    $atualizados = 0;
    $falhas = 0;
    $processados = 0;

    $atualizarLote = function ($lote) use ($steam, $forcar, &$atualizados, &$falhas, &$processados) {
        $resultados = $steam->buscarJogadoresOnlineEmLote($lote->pluck('steam_app_id')->all(), false);
        $casos = [];
        $parametros = [];
        $ids = [];

        foreach ($lote as $jogo) {
            $quantidade = $resultados[$jogo->steam_app_id] ?? null;

            if (!is_int($quantidade)) {
                $falhas++;
                continue;
            }

            $casos[] = 'WHEN ? THEN ?';
            $parametros[] = (int) $jogo->id_jogo;
            $parametros[] = $quantidade;
            $ids[] = (int) $jogo->id_jogo;
        }

        if ($ids) {
            $marcadores = implode(', ', array_fill(0, count($ids), '?'));
            $sql = 'UPDATE tb_jogo SET jogadores_online = CASE id_jogo '
                . implode(' ', $casos)
                . ' ELSE jogadores_online END, jogadores_online_atualizado_em = ?'
                . ' WHERE id_jogo IN (' . $marcadores . ')';

            $parametros[] = now()->toDateTimeString();
            array_push($parametros, ...$ids);
            DB::update($sql, $parametros);
            $atualizados += count($ids);
        }

        $processados += $lote->count();

        if ($forcar) {
            $this->line("Processados: {$processados} | Atualizados: {$atualizados} | Falhas: {$falhas}");
        }
    };

    if ($forcar) {
        $query = Jogo::query()->whereNotNull('steam_app_id');
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nenhum jogo com AppID da Steam foi encontrado.');
            return;
        }

        $quantidade = $todos ? $total : min($limite, $total);
        $this->info("Atualização manual: {$quantidade} jogo(s) selecionado(s) de {$total} com AppID.");

        if ($todos) {
            $query->select($colunas)->chunkById(100, $atualizarLote, 'id_jogo');
        } else {
            $jogos = $query
                ->orderBy('jogadores_online_atualizado_em')
                ->orderBy('id_jogo')
                ->limit($limite)
                ->get($colunas);

            foreach ($jogos->chunk(100) as $lote) {
                $atualizarLote($lote);
            }
        }
    } else {
        $limiteAtualizacao = now()->subMinutes(45);
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

        $atualizarLote($jogos);
    }

    Log::info('Steam: atualização de jogadores concluída', [
        'consultados' => $processados,
        'atualizados' => $atualizados,
        'sem_resposta_valida' => $falhas,
        'forcado' => $forcar,
    ]);

    $this->info($atualizados . ' jogo(s) atualizado(s). ' . $falhas . ' falha(s) mantiveram o último valor conhecido.');
})->purpose('Atualiza a popularidade dos jogos usando jogadores online da Steam.');
