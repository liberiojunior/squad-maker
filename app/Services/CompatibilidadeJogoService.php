<?php

namespace App\Services;

use App\Models\Jogo;
use App\Models\User;

class CompatibilidadeJogoService
{
    private const MINUTOS_ONLINE = 5;

    private const DIAS_RECENTE = 3;

    public function buscarJogadores(
        Jogo $jogo,
        User $usuarioAtual,
        ?int $nivelFiltro = null,
        int  $porPagina = 12
    ): array
    {
        $nivelUsuario =
            $this->buscarNivelUsuario(
                $jogo,
                $usuarioAtual
            );

        $limiteOnline = now()
            ->subMinutes(
                self::MINUTOS_ONLINE
            );

        $limiteRecente = now()
            ->subDays(
                self::DIAS_RECENTE
            );

        $query = User::query()
            ->join(
                'tb_jogo_usuario',
                'tb_jogo_usuario.id_usuario',
                '=',
                'tb_usuario.id_usuario'
            )
            ->where(
                'tb_jogo_usuario.id_jogo',
                $jogo->id_jogo
            )
            ->where(
                'tb_usuario.status_conta',
                'ativo'
            )
            ->where(
                'tb_usuario.id_usuario',
                '!=',
                $usuarioAtual->id_usuario
            )
            ->select('tb_usuario.*')
            ->addSelect(
                'tb_jogo_usuario.nivel_proficiencia as nivel_jogo'
            );

        if ($nivelFiltro !== null) {
            $query
                ->where(
                    'tb_jogo_usuario.nivel_proficiencia',
                    $nivelFiltro
                )
                ->orderByRaw(
                    '
                        CASE
                            WHEN tb_usuario.ultima_atividade >= ?
                                THEN 1
                            WHEN tb_usuario.ultima_atividade >= ?
                                THEN 2
                            ELSE 3
                        END
                    ',
                    [
                        $limiteOnline,
                        $limiteRecente,
                    ]
                );
        } elseif ($nivelUsuario !== null) {
            $query->orderByRaw(
                '
                    CASE
                        WHEN
                            tb_usuario.ultima_atividade >= ?
                            AND tb_jogo_usuario.nivel_proficiencia = ?
                            THEN 1

                        WHEN
                            tb_usuario.ultima_atividade >= ?
                            THEN 2

                        WHEN
                            tb_usuario.ultima_atividade >= ?
                            AND tb_jogo_usuario.nivel_proficiencia = ?
                            THEN 3

                        WHEN
                            tb_usuario.ultima_atividade >= ?
                            THEN 4

                        WHEN
                            tb_jogo_usuario.nivel_proficiencia = ?
                            THEN 5

                        ELSE 6
                    END
                ',
                [
                    $limiteOnline,
                    $nivelUsuario,

                    $limiteOnline,

                    $limiteRecente,
                    $nivelUsuario,

                    $limiteRecente,

                    $nivelUsuario,
                ]
            );
        } else {
            $query->orderByRaw(
                '
                    CASE
                        WHEN tb_usuario.ultima_atividade >= ?
                            THEN 1
                        WHEN tb_usuario.ultima_atividade >= ?
                            THEN 2
                        ELSE 3
                    END
                ',
                [
                    $limiteOnline,
                    $limiteRecente,
                ]
            );
        }

        $query->orderByRaw(
            "CRC32(
                CONCAT(
                    tb_usuario.id_usuario,
                    '-',
                    ?,
                    '-',
                    ?
                )
            )",
            [
                $jogo->id_jogo,
                now()->format('Y-m-d'),
            ]
        );

        $jogadores = $query
            ->paginate($porPagina)
            ->withQueryString();

        $jogadores
            ->getCollection()
            ->transform(
                function ($jogador) {
                    $presenca =
                        $this->calcularPresenca(
                            $jogador->ultima_atividade
                        );

                    $jogador->status_presenca =
                        $presenca['status'];

                    $jogador->status_texto =
                        $presenca['texto'];

                    return $jogador;
                }
            );

        return [
            'nivel_usuario' => $nivelUsuario,
            'jogadores' => $jogadores,
        ];
    }

    private function buscarNivelUsuario(
        Jogo $jogo,
        User $usuario
    ): ?int
    {
        $nivel = $usuario
            ->jogos()
            ->where(
                'tb_jogo.id_jogo',
                $jogo->id_jogo
            )
            ->value(
                'tb_jogo_usuario.nivel_proficiencia'
            );

        return $nivel !== null
            ? (int)$nivel
            : null;
    }

    private function calcularPresenca(
        $ultimaAtividade
    ): array
    {
        if (!$ultimaAtividade) {
            return [
                'status' => 'offline',
                'texto' => 'Offline',
            ];
        }

        $segundos =
            now()->timestamp
            - $ultimaAtividade->timestamp;

        $segundos = max(
            0,
            $segundos
        );

        if (
            $segundos
            <= self::MINUTOS_ONLINE * 60
        ) {
            return [
                'status' => 'online',
                'texto' => 'Online agora',
            ];
        }

        if (
            $segundos
            > self::DIAS_RECENTE * 86400
        ) {
            return [
                'status' => 'offline',
                'texto' => 'Offline',
            ];
        }

        if ($segundos < 3600) {
            $minutos = max(
                1,
                (int)floor(
                    $segundos / 60
                )
            );

            return [
                'status' => 'recente',
                'texto' =>
                    'Ativo há '
                    . $minutos
                    . ' min',
            ];
        }

        if ($segundos < 86400) {
            $horas = max(
                1,
                (int)floor(
                    $segundos / 3600
                )
            );

            return [
                'status' => 'recente',
                'texto' =>
                    'Ativo há '
                    . $horas
                    . (
                    $horas === 1
                        ? ' hora'
                        : ' horas'
                    ),
            ];
        }

        $dias = max(
            1,
            (int)floor(
                $segundos / 86400
            )
        );

        return [
            'status' => 'recente',
            'texto' =>
                'Ativo há '
                . $dias
                . (
                $dias === 1
                    ? ' dia'
                    : ' dias'
                ),
        ];
    }
}
