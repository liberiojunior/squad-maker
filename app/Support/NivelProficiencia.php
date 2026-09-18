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

    public static function todos(): array
    {
        return self::NIVEIS;
    }

    public static function nome(?int $nivel): string
    {
        return self::NIVEIS[$nivel] ?? 'Não definido';
    }
}
