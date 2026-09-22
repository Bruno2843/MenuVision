<?php

namespace App\Enums;

enum TableStatus: string
{
    case Libre = 'libre';
    case Ocupada = 'ocupada';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Libre->value => 'Libre',
            self::Ocupada->value => 'Ocupada',
        ];
    }
}
