<?php

namespace App\Enums;

enum Role: string
{
    case Administrador = 'administrador';
    case Mozo = 'mozo';
    case Cocina = 'cocina';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Administrador->value => 'Administrador',
            self::Mozo->value => 'Mozo',
            self::Cocina->value => 'Cocina',
        ];
    }
}
