<?php

namespace App\Support;

class NivelProficiencia
{
    public const NIVEIS = [
        1 => 'Iniciante',
        2 => 'Casual',
        3 => 'Engajado',
        4 => 'Competitivo',
        5 => 'Hardcore',
    ];

    private const DESCRICOES = [
        1 => 'Você está começando e procura jogadores pacientes para aprender e evoluir junto.',
        2 => 'Você procura jogadores que possam aproveitar a experiência com calma, sem foco rígido em desempenho.',
        3 => 'Você já conhece bem o jogo e procura pessoas presentes, participativas e dispostas a evoluir.',
        4 => 'Você procura jogadores focados em desempenho, estratégia e resultados.',
        5 => 'Você procura jogadores muito experientes, dedicados e dispostos a enfrentar os desafios mais exigentes.',
    ];

    public const DESCRICOES_SELECAO = [
        1 => 'Você ainda está aprendendo como o jogo funciona.',
        2 => 'Você já conhece o jogo e prefere uma experiência tranquila.',
        3 => 'Você conhece bem o jogo busca evoluir cada vez mais.',
        4 => 'Você tem boa experiência, busca desempenho e joga para vencer.',
        5 => 'Você domina o jogo e busca os desafios mais exigentes.',
    ];

    private const ICONES = [
        1 => 'images/niveis/iniciante.png',
        2 => 'images/niveis/casual.png',
        3 => 'images/niveis/engajado.png',
        4 => 'images/niveis/competitivo.png',
        5 => 'images/niveis/hardcore.png',
    ];

    private const CORES = [
        1 => '#aaa1b8',
        2 => '#39d353',
        3 => '#25c2e8',
        4 => '#4f7cff',
        5 => '#ec3bbd',
    ];

    public static function todos(): array
    {
        return self::NIVEIS;
    }

    public static function nome(?int $nivel): string
    {
        return self::NIVEIS[$nivel]
            ?? 'Não definido';
    }

    public static function detalhes(): array
    {
        $detalhes = [];
        foreach (self::NIVEIS as $valor => $nome) {
            $detalhes[$valor] = [
                'nome' => $nome,
                'descricao' => self::DESCRICOES[$valor],
                'icone' => self::ICONES[$valor],
                'cor' => self::CORES[$valor],
            ];
        }

        return $detalhes;
    }

    public static function descricoesSelecao(): array
    {
        return self::DESCRICOES_SELECAO;
    }

    public static function descricaoSelecao(
        ?int $nivel
    ): string {
        return self::DESCRICOES_SELECAO[$nivel] ?? '';
    }
}
